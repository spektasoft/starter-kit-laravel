<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\TwoFactorChallenge;
use App\Models\User;
use Filament\Schemas\Components\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\RecoveryCode;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_challenge_can_be_rendered(): void
    {
        Livewire::test(TwoFactorChallenge::class)
            ->assertStatus(200);
    }

    public function test_two_factor_challenge_form_has_proper_attributes(): void
    {
        /** @var Testable $testable */
        $testable = Livewire::test(TwoFactorChallenge::class);

        $testable->assertFormExists();
        $testable->assertFormFieldExists('two-factor-authentication.code');

        $testable->set('showRecovery', true)
            ->assertFormFieldExists('two-factor-authentication.code');
    }

    public function test_component_renders_authentication_code_form_by_default(): void
    {
        /** @var Testable $testable */
        $testable = Livewire::test(TwoFactorChallenge::class);

        $testable->assertSee(__('Please confirm access to your account by entering the authentication code provided by your authenticator application.'));
        $testable->assertSee(__('Code'));
        $testable->assertDontSee(__('Recovery Code'));
    }

    public function test_component_renders_recovery_code_form_when_show_recovery_is_true(): void
    {
        /** @var Testable $testable */
        $testable = Livewire::test(TwoFactorChallenge::class);

        $testable->set('showRecovery', true);

        $testable->assertSee(__('Please confirm access to your account by entering one of your emergency recovery codes.'));
        $testable->assertSee(__('Recovery Code'));
    }

    public function test_switch_to_recovery_code_link_toggles_show_recovery_and_refreshes_component(): void
    {
        $testable = Livewire::test(TwoFactorChallenge::class);
        $testable->assertSet('showRecovery', false);
        $testable->callFormComponentAction('two-factor-authentication', 'switch');
        $testable->assertSet('showRecovery', true);
    }

    public function test_switch_to_authentication_code_link_toggles_show_recovery_and_refreshes_component(): void
    {
        /** @var Testable $testable */
        $testable = Livewire::test(TwoFactorChallenge::class);

        $testable->set('showRecovery', true)
            ->callFormComponentAction('two-factor-authentication', 'switch')
            ->assertSet('showRecovery', false);
    }

    public function test_form_submission_binds_data(): void
    {
        /** @var Testable $testable */
        $testable = Livewire::test(TwoFactorChallenge::class);

        $testable->set('data.code', '123456')
            ->assertSet('data.code', '123456');

        /** @var TwoFactorChallenge $component */
        $component = $testable->instance();

        /** @var ?Section $section */
        $section = $component->form->getComponent('two-factor-authentication');
        $this->assertNotNull($section);

        $testable->callFormComponentAction('two-factor-authentication', 'log-in')
            ->assertSet('data.code', '123456');
    }

    public function test_successful_code_submission(): void
    {
        $provider = app(TwoFactorAuthenticationProvider::class);
        $user = User::factory()->create([
            'two_factor_secret' => encrypt($provider->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(Collection::times(8, function () {
                return RecoveryCode::generate();
            }))),
        ] + (Fortify::confirmsTwoFactorAuthentication() ? ['two_factor_confirmed_at' => now()] : []));

        /** @var string $decryptedSecret */
        $decryptedSecret = decrypt($user->two_factor_secret ?? '');

        $google2fa = new Google2FA;
        $otp = $google2fa->getCurrentOtp($decryptedSecret);

        Livewire::test(TwoFactorChallenge::class)
            ->fillForm(['code' => $otp])
            ->callFormComponentAction('two-factor-authentication', 'log-in')
            ->assertHasNoFormErrors()
            ->assertFormSet(['code' => $otp]);
    }
}
