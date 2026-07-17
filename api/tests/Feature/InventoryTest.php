<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use App\Support\Jwt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_index_reports_status_counts(): void
    {
        $user = User::factory()->create();
        $block = Block::factory()->create();
        Unit::factory(3)->create(['block_id' => $block->id]);
        Unit::factory(2)->create(['block_id' => $block->id, 'status' => 'sold']);

        $this->getJson('/api/v1/projects', $this->authHeader($user))
            ->assertOk()
            ->assertJsonPath('data.0.units_count', 5)
            ->assertJsonPath('data.0.available_count', 3)
            ->assertJsonPath('data.0.sold_count', 2);
    }

    public function test_grid_filters_by_type_and_max_price(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $block = Block::factory()->create(['project_id' => $project->id]);

        Unit::factory()->create(['block_id' => $block->id, 'type' => '2BHK', 'price' => 6_000_000]);
        Unit::factory()->create(['block_id' => $block->id, 'type' => '2BHK', 'price' => 7_500_000]);
        Unit::factory()->create(['block_id' => $block->id, 'type' => '3BHK', 'price' => 6_000_000]);

        $this->getJson("/api/v1/projects/{$project->id}/grid?type=2BHK&max_price=6500000", $this->authHeader($user))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', '2BHK')
            ->assertJsonPath('data.0.price', 6_000_000);
    }

    public function test_analytics_summary_shape(): void
    {
        $user = User::factory()->create();
        $block = Block::factory()->create();
        Unit::factory(4)->create(['block_id' => $block->id]);

        $this->getJson('/api/v1/analytics/summary', $this->authHeader($user))
            ->assertOk()
            ->assertJsonStructure([
                'inventory' => [['id', 'name', 'total', 'available', 'held', 'booked', 'sold', 'sold_value']],
                'funnel' => ['holds', 'confirmed', 'sold', 'cancelled'],
            ]);
    }

    public function test_guests_cannot_read_inventory(): void
    {
        $this->getJson('/api/v1/projects')->assertUnauthorized();
    }

    private function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.Jwt::issue($user->id)];
    }
}
