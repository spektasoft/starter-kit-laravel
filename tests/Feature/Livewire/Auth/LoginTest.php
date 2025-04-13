<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_can_be_rendered(): void
    {
        Livewire::test(Login::class)
            ->assertStatus(200);
    }

    public function test_login_has_form_and_fields(): void
    {
        $testable = Livewire::test(Login::class);
        $testable->assertFormExists();
        $testable->assertFormFieldExists('email');
        $testable->assertSeeHtml('name="email"');
        $testable->assertFormFieldExists('password');
        $testable->assertSeeHtml('name="password"');
        $testable->assertFormFieldExists('remember');
        $testable->assertSeeHtml('name="remember"');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        /** @var Testable $testable */
        $testable = Livewire::test(Login::class);
        $testable->set('data.email', $user->email);
        $testable->set('data.password', 'password');
        /** @var array<string, string> $formData */
        $formData = $testable->get('data');

        $response = $this->post(route('login'), $formData);

        $this->assertTrue(Auth::check());
        $response->assertRedirect();
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create(['password' => bcrypt('password')]);

        /** @var Testable $testable */
        $testable = Livewire::test(Login::class);
        $testable->set('data.email', 'invalid@example.com');
        $testable->set('data.password', 'wrong-password');
        /** @var array<string, string> $formData */
        $formData = $testable->get('data');

        $response = $this->post(route('login'), $formData);

        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(Auth::check());
    }

    public function test_email_field_is_required(): void
    {
        /** @var Testable $testable */
        $testable = Livewire::test(Login::class);
        $testable->set('data.password', 'password'); // Include password to avoid password error first

        /** @var Login */
        $component = $testable->instance();

        $this->expectException(ValidationException::class);

        $component->form->getState();
    }

    public function test_email_field_has_valid_email_format(): void
    {
        /** @var Testable $testable */
        $testable = Livewire::test(Login::class);
        $testable->set('data.email', 'invalid-email');
        $testable->set('data.password', 'password'); // Include password to avoid password error first

        /** @var Login */
        $component = $testable->instance();

        $this->expectException(ValidationException::class);

        $component->form->getState();
    }

    public function test_password_field_is_required(): void
    {
        /** @var Testable $testable */
        $testable = Livewire::test(Login::class);
        $testable->set('data.email', 'test@example.com'); // Include email to avoid email error first

        /** @var Login */
        $component = $testable->instance();

        $this->expectException(ValidationException::class);

        $component->form->getState();
    }

    public function test_form_validation_fails_when_email_field_is_missing(): void
    {
        /** @var Testable $testable */
        $testable = Livewire::test(Login::class);
        $testable->set('data.password', 'password'); // Include password to avoid password error first
        /** @var array<string, string> $formData */
        $formData = $testable->get('data');

        $response = $this->post(route('login'), $formData);
        $response->assertSessionHasErrors(['email']);
    }

    public function test_form_validation_fails_when_password_field_is_missing(): void
    {
        /** @var Testable $testable */
        $testable = Livewire::test(Login::class);
        $testable->set('data.email', 'test@example.com'); // Include email to avoid email error first
        /** @var array<string, string> $formData */
        $formData = $testable->get('data');

        $response = $this->post(route('login'), $formData);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('filament.admin.pages.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }
}
