<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Export>
 */
class ExportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'completed_at' => $this->faker->boolean(50) ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'file_disk' => 'public', // As used in the test
            'file_name' => $this->faker->boolean(80) ? 'exports/'.\Illuminate\Support\Str::ulid().'.csv' : null,
            'exporter' => $this->faker->word().'Exporter',
            'processed_rows' => $this->faker->numberBetween(0, 1000),
            'total_rows' => $this->faker->numberBetween(1000, 2000),
            'successful_rows' => $this->faker->numberBetween(0, 1000),
        ];
    }
}
