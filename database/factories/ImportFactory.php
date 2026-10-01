<?php

namespace Database\Factories;

use App\Models\Import; // Assuming User model exists and is needed for user_id
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Import>
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
            'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'file_name' => 'imports/'.Str::ulid().'.csv',
            'file_path' => 'imports/'.Str::ulid().'.csv',
            'importer' => $this->faker->randomElement(['UserImporter', 'ProductImporter', 'OrderImporter']),
            'processed_rows' => $processed = $this->faker->numberBetween(10, 1000),
            'total_rows' => $total = $this->faker->numberBetween($processed, $processed + 500),
            'successful_rows' => $this->faker->numberBetween(0, $processed),
            'user_id' => User::factory(), // Assumes User factory exists
        ];
    }
}
