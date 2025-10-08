<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_can_be_rendered(): void
    {
        Livewire::test(Register::class)
            ->assertStatus(200);
    }

    public function test_register_form_has_proper_attributes(): void
    {
        $testable = Livewire::test(Register::class);
        $testable->assertFormExists();
        $testable->assertFormFieldExists('name');
        $testable->assertSeeHtml('name="name"');
        $testable->assertSeeHtml('autocomplete="name"');
        $testable->assertFormFieldExists('email');
        $testable->assertSeeHtml('name="email"');
        $testable->assertSeeHtml('autocomplete="email"');
        $testable->assertFormFieldExists('password');
        $testable->assertSeeHtml('name="password"');
        $testable->assertFormFieldExists('passwordConfirmation');
        $testable->assertSeeHtml('name="password_confirmation"');
        $testable->assertFormFieldExists('terms');
        $testable->assertSeeHtml('name="terms"');
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registration_screen_cannot_be_rendered_if_support_is_disabled(): void
    {
        if (Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    public function test_new_users_can_register(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('filament.admin.pages.dashboard', absolute: false));
    }

    public function test_name_and_email_fields_repopulate_on_validation_error(): void
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'not-matching-password',
            'terms' => \Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $response->assertSessionHasErrors(['password']);
        $response->assertSessionHas('_old_input', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'terms' => \Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);
    }

    public function test_honeypot_field_is_present(): void
    {
        $response = $this->get('/register');
        /** @var string */
        $honeypotFieldName = config('honeypot.name_field_name');
        $response->assertSee($honeypotFieldName);
    }

    public function test_honeypot_detects_spam(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        /** @var string $honeypotFieldName */
        $honeypotFieldName = config('honeypot.name_field_name');

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
            $honeypotFieldName => 'filled',
        ]);

        $response->assertStatus(200);
        $this->assertGuest();
    }

    public function test_email_verification_notification_is_sent_when_enabled(): void
    {
        if (! Features::enabled(Features::registration()) || ! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Registration or email verification support is not enabled.');
        }

        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_email_verification_notification_is_not_sent_when_disabled(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        // Temporarily disable email verification for this test
        $this->app['config']->set('fortify.features', [Features::registration()]);

        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $user = User::where('email', 'test@example.com')->first();
        Notification::assertNothingSentTo($user);
    }
}
