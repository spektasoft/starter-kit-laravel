<?php

namespace Tests\Feature;

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateUserProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalization_persists_when_email_is_not_changed(): void
    {
        $this->app['config']->set('fortify.lowercase_usernames', true);
        $this->app['config']->set('fortify.username', 'email'); // Default configuration

        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $action = new UpdateUserProfileInformation;

        // Simulate updating name but keeping email the same, with uppercase input for email
        $input = [
            'name' => 'Updated Name',
            'email' => 'ORIGINAL@EXAMPLE.COM', // Uppercase email (should be normalized)
        ];

        $action->update($user, $input);

        // Reload user from database
        $user->refresh();

        // Assert that the name was updated
        $this->assertEquals('Updated Name', $user->name);

        // Assert that the email was normalized to lowercase even though it didn't change
        $this->assertEquals('original@example.com', $user->email);
    }

    public function test_normalization_works_when_email_changes(): void
    {
        $this->app['config']->set('fortify.lowercase_usernames', true);
        $this->app['config']->set('fortify.username', 'email');

        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $action = new UpdateUserProfileInformation;

        // Simulate updating both name and email
        $input = [
            'name' => 'Updated Name',
            'email' => 'NEWUSER@EXAMPLE.COM', // Different email in uppercase
        ];

        $action->update($user, $input);

        // Reload user from database
        $user->refresh();

        // Assert that the name was updated
        $this->assertEquals('Updated Name', $user->name);

        // Assert that the email was normalized to lowercase
        $this->assertEquals('newuser@example.com', $user->email);
    }
}
