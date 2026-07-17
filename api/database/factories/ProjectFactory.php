<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' '.fake()->randomElement(['Heights', 'Gardens', 'Enclave', 'Residency']),
            'city' => fake()->randomElement(['Chennai', 'Bengaluru', 'Hyderabad']),
            'lat' => fake()->latitude(12.8, 13.2),
            'lng' => fake()->longitude(77.4, 80.3),
            'description' => fake()->sentence(14),
        ];
    }
}
