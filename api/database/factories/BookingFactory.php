<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('98########'),
            'stage' => 'hold',
            'price_snapshot' => fake()->numberBetween(35, 90) * 100000,
            'hold_expires_at' => now()->addHours(48),
        ];
    }
}
