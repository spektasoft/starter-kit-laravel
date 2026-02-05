<?php

namespace Tests\Unit\Actions\Fortify;

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Fortify\Fortify;
use Tests\TestCase;

class UpdateUserProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_username_is_normalized_to_lowercase_when_configured(): void
    {
        // Arrange
        Config::set('fortify.lowercase_usernames', true);
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $action = new UpdateUserProfileInformation;
        $input = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        // Act
        $action->update($user, $input);

        // Assert
        $this->assertEquals('john@example.com', $user->fresh()->{Fortify::username()});
    }

    public function test_username_is_not_normalized_when_config_not_set(): void
    {
        // Arrange
        Config::set('fortify.lowercase_usernames', false);
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $action = new UpdateUserProfileInformation;
        $input = [
            'name' => 'John Doe',
            'email' => 'JOHNDOE@example.com',
        ];

        // Act
        $action->update($user, $input);

        // Assert
        $this->assertEquals('JOHNDOE@example.com', $user->fresh()->{Fortify::username()});
    }
}
