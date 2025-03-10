<?php

namespace Database\Factories;

use App\Models\Definition;
use App\Models\Entry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entry>
 */
class ExampleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'definition_id' => Definition::factory(),
            'sentence' => fake('zh_CN')->sentence(),
        ];
    }
}
