<?php

namespace Database\Factories;

use App\Enums\Page\Status;
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
        $content = [
            'en' => $this->faker->text,
        ];

        return [
            'creator_id' => $creator->id,
            'title' => $title,
            'content' => $content,
            'status' => Status::Draft,
        ];
    }
}
