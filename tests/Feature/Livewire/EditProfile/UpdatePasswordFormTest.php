<?php

namespace Tests\Feature\Livewire\EditProfile;

use App\Livewire\EditProfile\UpdatePasswordForm;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UpdatePasswordFormTest extends TestCase
{
    public function test_update_password_form_can_be_rendered(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(UpdatePasswordForm::class)
            ->assertStatus(200);
    }

    public function test_password_can_be_updated(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        $testable = Livewire::test(UpdatePasswordForm::class);
        $testable->fillForm([
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);
        $testable->call('updatePassword');

        /** @var User */
        $freshUser = $user->fresh();
        $this->assertTrue(Hash::check('new-password', $freshUser->password));
    }

    public function test_current_password_must_be_correct(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        $testable = Livewire::test(UpdatePasswordForm::class);
        $testable->fillForm([
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);
        $testable->call('updatePassword');
        $testable->assertHasErrors(['data.current_password']);

        /** @var User */
        $freshUser = $user->fresh();
        $this->assertTrue(Hash::check('password', $freshUser->password));
    }

    public function test_new_passwords_must_match(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        $testable = Livewire::test(UpdatePasswordForm::class);
        $testable->fillForm([
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'wrong-password',
        ]);
        $testable->call('updatePassword');
        $testable->assertHasErrors(['data.password']);

        /** @var User */
        $freshUser = $user->fresh();
        $this->assertTrue(Hash::check('password', $freshUser->password));
    }
}
