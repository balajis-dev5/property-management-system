<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Unit;
use App\Models\User;
use App\Support\Jwt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_hold_claims_the_unit_and_snapshots_the_price(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['price' => 6_100_000]);

        $this->postJson("/api/v1/units/{$unit->id}/hold", [
            'customer_name' => 'Meena Krishnan',
            'customer_phone' => '9840012345',
        ], $this->authHeader($user))
            ->assertCreated()
            ->assertJsonPath('data.stage', 'hold')
            ->assertJsonPath('data.price_snapshot', 6_100_000);

        $this->assertSame('held', $unit->fresh()->status);
    }

    public function test_second_hold_on_same_unit_is_rejected_with_409(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create();
        $payload = ['customer_name' => 'First Buyer', 'customer_phone' => '9840000001'];

        $this->postJson("/api/v1/units/{$unit->id}/hold", $payload, $this->authHeader($user))->assertCreated();

        $this->postJson("/api/v1/units/{$unit->id}/hold", [
            'customer_name' => 'Second Buyer',
            'customer_phone' => '9840000002',
        ], $this->authHeader($user))
            ->assertStatus(409)
            ->assertJsonPath('code', 'UNIT_NOT_AVAILABLE');

        $this->assertSame(1, Booking::count());
    }

    public function test_price_snapshot_survives_a_later_price_change(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['price' => 5_000_000]);

        $this->postJson("/api/v1/units/{$unit->id}/hold", [
            'customer_name' => 'Snapshot Test',
            'customer_phone' => '9840000003',
        ], $this->authHeader($user))->assertCreated();

        $unit->update(['price' => 5_500_000]);

        $this->assertSame(5_000_000, Booking::first()->price_snapshot);
    }

    public function test_full_lifecycle_hold_to_sold_writes_audit_trail(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create();

        $bookingId = $this->postJson("/api/v1/units/{$unit->id}/hold", [
            'customer_name' => 'Lifecycle Buyer',
            'customer_phone' => '9840000004',
        ], $this->authHeader($user))->json('data.id');

        $this->postJson("/api/v1/bookings/{$bookingId}/confirm", [], $this->authHeader($user))
            ->assertOk()
            ->assertJsonPath('data.stage', 'booked');
        $this->assertSame('booked', $unit->fresh()->status);

        $this->postJson("/api/v1/bookings/{$bookingId}/complete", [], $this->authHeader($user))
            ->assertOk()
            ->assertJsonPath('data.stage', 'sold');
        $this->assertSame('sold', $unit->fresh()->status);

        $events = $this->getJson("/api/v1/bookings/{$bookingId}", $this->authHeader($user))
            ->json('data.events');

        $this->assertCount(3, $events); // hold, booked, sold
    }

    public function test_selling_straight_from_hold_is_rejected(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create();

        $bookingId = $this->postJson("/api/v1/units/{$unit->id}/hold", [
            'customer_name' => 'Impatient Buyer',
            'customer_phone' => '9840000005',
        ], $this->authHeader($user))->json('data.id');

        $this->postJson("/api/v1/bookings/{$bookingId}/complete", [], $this->authHeader($user))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'INVALID_TRANSITION');
    }

    public function test_cancel_frees_the_unit(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create();

        $bookingId = $this->postJson("/api/v1/units/{$unit->id}/hold", [
            'customer_name' => 'Change of Mind',
            'customer_phone' => '9840000006',
        ], $this->authHeader($user))->json('data.id');

        $this->postJson("/api/v1/bookings/{$bookingId}/cancel", [], $this->authHeader($user))->assertOk();

        $this->assertSame('available', $unit->fresh()->status);
    }

    private function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.Jwt::issue($user->id)];
    }
}
