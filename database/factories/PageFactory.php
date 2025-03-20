<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $creator = User::factory()->create();
        $title = [
            'en' => $this->faker->words(5, true),
        ];
        $content = $this->faker->optional()->text;

        return [
            'creator_id' => $creator->id,
            'title' => json_encode($title),
            'content' => $content ? json_encode(['en' => $content]) : null,
        ];
    }
}
