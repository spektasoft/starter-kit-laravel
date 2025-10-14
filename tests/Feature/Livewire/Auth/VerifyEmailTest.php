<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\VerifyEmail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_email_can_be_rendered(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        /** @var User */
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        /** @var Testable */
        $testable = Livewire::test(VerifyEmail::class);

        $testable->assertStatus(200);
    }

    public function test_email_verification_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        Event::fake();

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);

        /** @var User */
        $freshUser = $user->fresh();
        $this->assertTrue($freshUser->hasVerifiedEmail());
        $response->assertRedirect(route('filament.admin.pages.dashboard', absolute: false).'?verified=1');
    }

    public function test_email_can_not_verified_with_invalid_hash(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        /** @var User */
        $freshUser = $user->fresh();
        $this->assertFalse($freshUser->hasVerifiedEmail());
    }

    public function test_resend_verification_email_button_works(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        /** @var User */
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        /** @var Testable */
        $testable = Livewire::test(VerifyEmail::class);
        $testable->assertStatus(200);
        $testable->assertFormExists();
        $testable->assertFormComponentExists('actions');
        $testable->mountFormComponentAction('actions', 'resendNotification');
        $testable->callMountedFormComponentAction();
        $testable->assertStatus(200);
    }
}
