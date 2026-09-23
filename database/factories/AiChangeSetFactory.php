<?php

namespace Database\Factories;

use App\Models\AiChangeSet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiChangeSet>
 */
class AiChangeSetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'world' => 'retro',
            'title' => fake()->sentence(4),
            'prompt' => fake()->sentence(),
            'status' => AiChangeSet::STATUS_DRAFT,
            'revision' => 1,
            'operations' => [[
                'type' => 'create_quest',
                'key' => 'factory_quest',
                'data' => [
                    'name' => fake()->sentence(3),
                    'steps' => [[
                        'key' => 'factory_step',
                        'name' => fake()->sentence(3),
                        'description' => fake()->sentence(),
                    ]],
                ],
            ]],
            'validation' => [
                'valid' => true,
                'errors' => [],
                'warnings' => ['Fabryka nie zapisuje danych świata.'],
            ],
        ];
    }
}
