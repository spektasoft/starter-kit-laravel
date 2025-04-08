<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\ConfirmPassword;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ConfirmPasswordTest extends TestCase
{
    public function test_confirm_password_can_be_rendered(): void
    {
        $component = Livewire::test(ConfirmPassword::class);
        $component->assertStatus(200);
    }
    
    public function test_confirm_password_form_has_proper_attributes(): void
    {
        $testable = Livewire::test(ConfirmPassword::class);
        $testable->assertFormExists();
        $testable->assertFormFieldExists('password');
        $testable->assertSeeHtml('name="password"');
    }

    public function test_confirm_password_form_validation(): void
    {
        $testable = Livewire::test(ConfirmPassword::class);
        /** @var ConfirmPassword */
        $component = $testable->instance();

        $form = $component->form;

        $form->fill(['password' => '']);

        $this->expectException(ValidationException::class);

        $component->form->getState();
    }

    public function test_password_is_confirmed_successfully(): void
    {
        $testable = Livewire::test(ConfirmPassword::class);
        $user = User::factory()->create();
        $this->actingAs($user);

        $testable->set('data.password', 'password');

        /** @var ConfirmPassword */
        $component = $testable->instance();

        $response = $this->post(route('password.confirm'), $component->form->getState());

        $this->assertTrue(session()->has('auth.password_confirmed_at'));

        /** @var ?string */
        $redirectUrl = config('fortify.home');
        $response->assertRedirect($redirectUrl);
    }

    public function test_password_is_not_confirmed_with_incorrect_password(): void
    {
        $testable = Livewire::test(ConfirmPassword::class);
        $user = User::factory()->create();
        $this->actingAs($user);

        $testable->set('data.password', 'wrong-password');

        /** @var ConfirmPassword */
        $component = $testable->instance();

        $response = $this->post(route('password.confirm'), $component->form->getState());
        $response->assertSessionHasErrors(['password']);

        $this->assertFalse(session()->has('auth.password_confirmed_at'));
    }

    public function test_confirm_password_screen_can_be_rendered(): void
    {
        /** @var User */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/user/confirm-password');

        $response->assertStatus(200);
    }

    public function test_password_can_be_confirmed(): void
    {
        /** @var User */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/user/confirm-password', [
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_password_is_not_confirmed_with_invalid_password(): void
    {
        /** @var User */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/user/confirm-password', [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
    }
}
