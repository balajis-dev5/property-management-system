<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Booking;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Factories\UnitFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $permissions = collect([
            ['name' => 'users.view', 'label' => 'View the user directory'],
            ['name' => 'users.manage', 'label' => 'Invite and deactivate users'],
        ])->map(fn (array $p) => Permission::create($p));

        $admin = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $admin->permissions()->attach($permissions->pluck('id'));
        Role::create(['name' => 'agent', 'label' => 'Sales Agent']);

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role_id' => $admin->id,
        ]);

        $projects = [
            ['name' => 'Marina Heights', 'city' => 'Chennai', 'lat' => 13.0475, 'lng' => 80.2824,
                'description' => 'Sea-facing towers near Marina Beach with clubhouse and podium gardens.', 'blocks' => 4, 'floors' => 10],
            ['name' => 'Velachery Gardens', 'city' => 'Chennai', 'lat' => 12.9815, 'lng' => 80.2180,
                'description' => 'Mid-rise community beside the IT corridor; lake-view east wing.', 'blocks' => 3, 'floors' => 8],
            ['name' => 'OMR Enclave', 'city' => 'Chennai', 'lat' => 12.8996, 'lng' => 80.2209,
                'description' => 'Compact smart homes on Old Mahabalipuram Road, metro-adjacent.', 'blocks' => 3, 'floors' => 6],
        ];

        foreach ($projects as $spec) {
            $project = Project::create([
                'name' => $spec['name'], 'city' => $spec['city'],
                'lat' => $spec['lat'], 'lng' => $spec['lng'], 'description' => $spec['description'],
            ]);

            foreach (range(0, $spec['blocks'] - 1) as $b) {
                $block = Block::create([
                    'project_id' => $project->id,
                    'name' => 'Block '.chr(65 + $b),
                    'floors' => $spec['floors'],
                ]);

                foreach (range(1, $spec['floors']) as $floor) {
                    foreach (range(1, 4) as $pos) {
                        $type = Unit::TYPES[($floor + $pos) % 3];

                        Unit::create([
                            'block_id' => $block->id,
                            'unit_no' => $floor * 100 + $pos,
                            'floor' => $floor,
                            'type' => $type,
                            'facing' => Unit::FACINGS[($b + $pos) % 4],
                            'area_sqft' => match ($type) {
                                '1BHK' => 620 + $pos * 15,
                                '2BHK' => 1050 + $pos * 25,
                                default => 1520 + $pos * 40,
                            },
                            'price' => UnitFactory::price($type, $floor),
                            'status' => 'available',
                        ]);
                    }
                }
            }
        }

        // Book out a slice of inventory so grids and dashboards look lived-in.
        Unit::query()->inRandomOrder()->limit(130)->get()
            ->each(function (Unit $unit, int $i) {
                $target = match (true) {
                    $i < 12 => 'held',
                    $i < 50 => 'booked',
                    default => 'sold',
                };

                $booking = Booking::create([
                    'unit_id' => $unit->id,
                    'customer_name' => fake()->name(),
                    'customer_phone' => fake()->numerify('98########'),
                    'stage' => 'hold',
                    'price_snapshot' => $unit->price,
                    'hold_expires_at' => now()->addHours(fake()->numberBetween(2, 72)),
                ]);
                $booking->events()->create(['from_stage' => null, 'to_stage' => 'hold', 'note' => 'Hold placed']);

                if ($target !== 'held') {
                    $booking->transitionTo('booked', 'Booking confirmed');
                }
                if ($target === 'sold') {
                    $booking->transitionTo('sold', 'Sale registered');
                }

                $unit->update(['status' => $target]);
            });

        // A few cancelled bookings for funnel realism.
        Unit::query()->where('status', 'available')->inRandomOrder()->limit(15)->get()
            ->each(function (Unit $unit) {
                $booking = Booking::create([
                    'unit_id' => $unit->id,
                    'customer_name' => fake()->name(),
                    'customer_phone' => fake()->numerify('98########'),
                    'stage' => 'hold',
                    'price_snapshot' => $unit->price,
                    'hold_expires_at' => now()->subHours(2),
                ]);
                $booking->events()->create(['from_stage' => null, 'to_stage' => 'hold', 'note' => 'Hold placed']);
                $booking->transitionTo('cancelled', 'Hold expired');
            });
    }
}
