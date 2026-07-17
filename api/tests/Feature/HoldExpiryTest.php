<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HoldExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_holds_are_released_and_units_freed(): void
    {
        $expired = Unit::factory()->create(['status' => 'held']);
        $active = Unit::factory()->create(['status' => 'held']);

        $expiredBooking = Booking::factory()->create([
            'unit_id' => $expired->id,
            'hold_expires_at' => now()->subMinutes(5),
        ]);
        $activeBooking = Booking::factory()->create([
            'unit_id' => $active->id,
            'hold_expires_at' => now()->addHours(4),
        ]);

        $this->artisan('holds:release')->assertSuccessful();

        $this->assertSame('cancelled', $expiredBooking->fresh()->stage);
        $this->assertSame('available', $expired->fresh()->status);

        $this->assertSame('hold', $activeBooking->fresh()->stage);
        $this->assertSame('held', $active->fresh()->status);
    }

    public function test_release_writes_an_audit_event(): void
    {
        $unit = Unit::factory()->create(['status' => 'held']);
        $booking = Booking::factory()->create([
            'unit_id' => $unit->id,
            'hold_expires_at' => now()->subMinute(),
        ]);

        $this->artisan('holds:release');

        $this->assertDatabaseHas('booking_events', [
            'booking_id' => $booking->id,
            'to_stage' => 'cancelled',
            'note' => 'Hold expired',
        ]);
    }
}
