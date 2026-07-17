<?php

namespace Database\Factories;

use App\Models\Block;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Block>
 */
class BlockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => 'Block '.fake()->unique()->randomLetter(),
            'floors' => fake()->numberBetween(4, 12),
        ];
    }
}
