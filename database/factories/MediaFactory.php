<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'disk' => 'public',
            'directory' => $this->faker->randomElement(['media', 'images', 'videos']),
            'visibility' => $this->faker->randomElement(['public', 'private']),
            'name' => $this->faker->word,
            'path' => $this->faker->word,
            'width' => $this->faker->optional()->numberBetween(100, 1000),
            'height' => $this->faker->optional()->numberBetween(100, 1000),
            'size' => $this->faker->optional()->numberBetween(100, 10000),
            'type' => $this->faker->randomElement(['image', 'video', 'audio']),
            'ext' => $this->faker->fileExtension(),
            'alt' => $this->faker->optional()->sentence,
            'title' => $this->faker->optional()->sentence,
            'description' => $this->faker->optional()->paragraph,
            'caption' => $this->faker->optional()->paragraph,
            'exif' => [],
            'curations' => [],
        ];
    }
}
