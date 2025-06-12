<?php

namespace Database\Factories;

use App\Models\User; // Assuming User model exists and is needed for user_id
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Import>
 */
class ImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'completed_at' => $this->faker->boolean(70) ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'file_name' => $this->faker->boolean(80) ? 'imports/'.\Illuminate\Support\Str::ulid().'.csv' : null,
            'file_path' => 'imports/'.\Illuminate\Support\Str::ulid().'.csv',
            'importer' => $this->faker->randomElement(['UserImporter', 'ProductImporter', 'OrderImporter']),
            'processed_rows' => $processed = $this->faker->numberBetween(10, 1000),
            'total_rows' => $total = $this->faker->numberBetween($processed, $processed + 500),
            'successful_rows' => $this->faker->numberBetween(0, $processed),
            'user_id' => User::factory(), // Assumes User factory exists
        ];
    }
}
