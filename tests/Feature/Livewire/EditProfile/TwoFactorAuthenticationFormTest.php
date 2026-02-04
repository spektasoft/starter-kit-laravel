<?php

namespace Tests\Feature\Livewire\EditProfile;

use App\Livewire\EditProfile\TwoFactorAuthenticationForm;
use App\Models\User;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorAuthenticationFormTest extends TestCase
{
    public function test_two_factor_authentication_form_can_be_rendered(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two factor authentication is not enabled.');
        }

        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(TwoFactorAuthenticationForm::class)
            ->assertStatus(200);
    }

    public function test_two_factor_authentication_can_be_enabled(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two factor authentication is not enabled.');
        }

        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(TwoFactorAuthenticationForm::class)
            ->call('enableTwoFactorAuthentication', 'password');

        $user = $user->fresh();

        $this->assertNotNull($user?->two_factor_secret);
        $this->assertCount(8, $user->recoveryCodes());
    }

    public function test_recovery_codes_can_be_regenerated(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two factor authentication is not enabled.');
        }

        /** @var User */
        $user = User::factory()->create([
            'two_factor_secret' => 'abcd',
        ]);
        $this->actingAs($user);

        $testable = Livewire::test(TwoFactorAuthenticationForm::class);
        $testable->call('enableTwoFactorAuthentication', 'password');
        $testable->call('regenerateRecoveryCodes', 'password');

        /** @var User */
        $user = $user->fresh();

        $testable->call('regenerateRecoveryCodes', 'password');

        /** @var User */
        $freshUser = $user->fresh();

        $this->assertCount(8, $user->recoveryCodes());
        $this->assertCount(8, array_diff($user->recoveryCodes(), $freshUser->recoveryCodes()));
    }

    public function test_two_factor_authentication_can_be_disabled(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two factor authentication is not enabled.');
        }

        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        $testable = Livewire::test(TwoFactorAuthenticationForm::class);
        $testable->call('enableTwoFactorAuthentication', 'password');

        /** @var User */
        $freshUser = $user->fresh();
        $this->assertNotNull($freshUser->two_factor_secret);

        $testable->call('disableTwoFactorAuthentication', 'password');

        /** @var User */
        $freshUser = $user->fresh();
        $this->assertNull($freshUser->two_factor_secret);
    }

    public function test_confirmation_requires_six_digits(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped();
        }

        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(TwoFactorAuthenticationForm::class)
            ->set('code', '123')
            ->call('confirmTwoFactorAuthentication')
            ->assertHasErrors(['code']);
    }

    public function test_decryption_failure_is_handled_gracefully(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped();
        }

        $user = User::factory()->create([
            'two_factor_secret' => 'invalid-encrypted-data',
        ]);
        $this->actingAs($user);

        // Test that the method doesn't throw an exception when encountering invalid data
        $component = Livewire::test(TwoFactorAuthenticationForm::class);

        // This should not throw an exception and should complete without errors
        $component->call('getSetupKey');

        // Optionally verify the component remains in a valid state
        $component->assertSet('two_factor_secret', null);
    }
}
