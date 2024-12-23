<?php

namespace Database\Factories;

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
            'creator_id' => $this->faker->ulid,
            'disk' => $this->faker->randomElement(['public', 'private']),
            'directory' => $this->faker->randomElement(['media', 'images', 'videos']),
            'visibility' => $this->faker->randomElement(['public', 'private']),
            'name' => $this->faker->word,
            'path' => $this->faker->filePath,
            'width' => $this->faker->optional()->numberBetween(100, 1000),
            'height' => $this->faker->optional()->numberBetween(100, 1000),
            'size' => $this->faker->optional()->numberBetween(100, 10000),
            'type' => $this->faker->randomElement(['image', 'video', 'audio']),
            'ext' => $this->faker->fileExtension,
            'alt' => $this->faker->optional()->sentence,
            'title' => $this->faker->optional()->sentence,
            'description' => $this->faker->optional()->paragraph,
            'caption' => $this->faker->optional()->paragraph,
            'exif' => $this->faker->optional()->paragraph,
            'curations' => $this->faker->optional()->paragraph,
        ];
    }
}
