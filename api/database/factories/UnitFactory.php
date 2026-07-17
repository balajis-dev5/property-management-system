<?php

namespace Database\Factories;

use App\Models\Block;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(Unit::TYPES);
        $floor = fake()->numberBetween(1, 8);

        return [
            'block_id' => Block::factory(),
            'unit_no' => $floor.str_pad((string) fake()->unique()->numberBetween(1, 9999), 2, '0', STR_PAD_LEFT),
            'floor' => $floor,
            'type' => $type,
            'facing' => fake()->randomElement(Unit::FACINGS),
            'area_sqft' => match ($type) {
                '1BHK' => fake()->numberBetween(550, 750),
                '2BHK' => fake()->numberBetween(950, 1250),
                default => fake()->numberBetween(1400, 1900),
            },
            'price' => self::price($type, $floor),
            'status' => 'available',
        ];
    }

    /** Base by type + floor rise — the pricing rule the README documents. */
    public static function price(string $type, int $floor): int
    {
        $base = match ($type) {
            '1BHK' => 3_500_000,
            '2BHK' => 5_800_000,
            default => 8_200_000,
        };

        return $base + $floor * 75_000;
    }
}
