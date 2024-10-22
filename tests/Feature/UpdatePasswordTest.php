<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\Http\Livewire\UpdatePasswordForm;
use Livewire\Livewire;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(UpdatePasswordForm::class)
            ->set('state', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->call('updatePassword');

        /** @var User */
        $freshUser = $user->fresh();
        $this->assertTrue(Hash::check('new-password', $freshUser->password));
    }

    public function test_current_password_must_be_correct(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(UpdatePasswordForm::class)
            ->set('state', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        /** @var User */
        $freshUser = $user->fresh();
        $this->assertTrue(Hash::check('password', $freshUser->password));
    }

    public function test_new_passwords_must_match(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(UpdatePasswordForm::class)
            ->set('state', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'wrong-password',
            ])
            ->call('updatePassword')
            ->assertHasErrors(['password']);

        /** @var User */
        $freshUser = $user->fresh();
        $this->assertTrue(Hash::check('password', $freshUser->password));
    }
}
