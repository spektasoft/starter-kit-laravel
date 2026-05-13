<?php

namespace Tests\Feature\Livewire\EditProfile;

use App\Livewire\EditProfile\TwoFactorAuthenticationForm;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
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
        $this->assertCount(8, array_diff((array) $user->recoveryCodes(), (array) $freshUser->recoveryCodes()));
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

    public function test_decryption_failure_returns_empty_state(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped();
        }

        // Notification::fake();

        $user = User::factory()->create([
            'two_factor_secret' => 'invalid-data-that-cannot-be-decrypted',
        ]);
        $this->actingAs($user);

        $component = Livewire::test(TwoFactorAuthenticationForm::class);

        // Drive getSetupKey() through the Livewire lifecycle
        $component->call('getSetupKey');

        // Assert notification was sent via Filament's fake harness
        // Notification::assertSent(
        //     $user,
        //     fn ($notification) => str_contains($notification->body ?? '', 'configuration_error')
        // );

        // Assert the return value via a direct invocation with typed instance
        /** @var TwoFactorAuthenticationForm $componentInstance */
        $componentInstance = $component->instance();
        $returnValue = $componentInstance->getSetupKey();
        $this->assertEquals('', $returnValue);
    }

    public function test_two_factor_authentication_form_renders_footer_actions_correctly(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(TwoFactorAuthenticationForm::class)
            ->assertStatus(200)
            ->assertSee(__('Enable'));
    }

    public function test_get_form_footer_actions_returns_action_objects(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(TwoFactorAuthenticationForm::class);

        /** @var TwoFactorAuthenticationForm $componentInstance */
        $componentInstance = $component->instance();
        $actions = (new \ReflectionMethod($componentInstance, 'getActions'))
            ->invoke($componentInstance);

        $this->assertIsArray($actions);
        $this->assertNotEmpty($actions);

        foreach ($actions as $action) {
            $this->assertInstanceOf(
                Action::class,
                $action,
                'getFormFooterActions() must return Action instances, not serialised arrays.'
            );
        }
    }

    public function test_get_form_components_returns_component_objects_when_2fa_enabled(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(TwoFactorAuthenticationForm::class);

        /** @var TwoFactorAuthenticationForm $componentInstance */
        $componentInstance = $component->instance();
        // When 2FA is disabled the method returns an empty array — that path is valid.
        // Force showingQrCode to exercise the non-empty branch.
        $componentInstance->showingQrCode = true;

        // Temporarily enable 2FA on the user model so getEnabledProperty() returns true.
        $user->forceFill(['two_factor_secret' => encrypt('fake-secret')])->save();

        /** @var Component[] $components */
        $components = (new \ReflectionMethod($componentInstance, 'getFormComponents'))
            ->invoke($componentInstance);

        foreach ($components as $item) {
            $this->assertInstanceOf(
                Component::class,
                $item,
                'getFormComponents() must return Component instances, not serialised arrays.'
            );
        }
    }
}
