<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\ForgotPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_can_be_rendered(): void
    {
        /** @var Testable */
        $testable = Livewire::test(ForgotPassword::class);
        $testable->assertStatus(200);
    }

    public function test_forgot_password_form_has_proper_attributes(): void
    {
        $testable = Livewire::test(ForgotPassword::class);
        $testable->assertFormExists();
        $testable->assertFormFieldExists('email');
        $testable->assertSeeHtml('name="email"');
    }

    public function test_email_is_required(): void
    {
        /** @var Testable */
        $testable = Livewire::test(ForgotPassword::class);

        /** @var ForgotPassword */
        $component = $testable->instance();
        $component->data['email'] = '';
        $testable->set('data', $component->data);

        $this->expectException(ValidationException::class);

        $component->form->getState();
    }

    public function test_email_must_be_valid_email(): void
    {
        /** @var Testable */
        $testable = Livewire::test(ForgotPassword::class);

        /** @var ForgotPassword */
        $component = $testable->instance();
        $component->data['email'] = 'invalid-email';
        $testable->set('data', $component->data);

        $this->expectException(ValidationException::class);

        $component->form->getState();
    }

    public function test_forgot_password_form_redirects_to_password_email_route(): void
    {
        /** @var Testable */
        $testable = Livewire::test(ForgotPassword::class);

        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        /** @var ForgotPassword */
        $component = $testable->instance();
        $component->data['email'] = 'test@example.com';
        $testable->set('data', $component->data);

        /** @var array<string, string> */
        $formData = $testable->get('data');
        $response = $this->post(route('password.email'), $formData);

        $response->assertRedirect(route('home'));
    }
}
