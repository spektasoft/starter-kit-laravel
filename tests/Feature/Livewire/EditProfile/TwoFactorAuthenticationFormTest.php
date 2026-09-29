<?php

namespace Tests\Feature\Livewire\EditProfile;

use App\Livewire\EditProfile\TwoFactorAuthenticationForm;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class TwoFactorAuthenticationFormTest extends TestCase
{
    public function test_presentation_trait_methods_are_accessible_on_component(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two factor authentication is not enabled.');
        }

        $user = User::factory()->create([
            'two_factor_secret' => encrypt('TESTSECRETKEY123'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-a', 'code-b'])),
            'two_factor_confirmed_at' => now(),
        ]);
        $this->actingAs($user);

        $testable = Livewire::test(TwoFactorAuthenticationForm::class);
        /** @var TwoFactorAuthenticationForm $instance */
        $instance = $testable->instance();

        $this->assertTrue($instance->getEnabledProperty());
        $this->assertSame('TESTSECRETKEY123', $instance->getSetupKey());
        $this->assertSame(['code-a', 'code-b'], $instance->getRecoveryCodes());
        $this->assertNotEmpty($instance->showTwoFactorQrCodeSvg());
    }

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

    public function test_form_renders_disabled_state_when_two_factor_is_not_enabled(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two factor authentication is not enabled.');
        }

        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(TwoFactorAuthenticationForm::class)
            ->assertSee(__('You have not enabled two factor authentication.'))
            ->assertDontSee(__('You have enabled two factor authentication.'));
    }

    public function test_form_renders_enabled_state_when_two_factor_is_confirmed(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two factor authentication is not enabled.');
        }

        /** @var User */
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('SECRETKEYSECRETKEY'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-one', 'code-two'])),
            'two_factor_confirmed_at' => now(),
        ]);
        $this->actingAs($user);

        Livewire::test(TwoFactorAuthenticationForm::class)
            ->assertSee(__('You have enabled two factor authentication.'))
            ->assertDontSee(__('You have not enabled two factor authentication.'));
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

    public function test_action_trait_handles_showing_recovery_codes_actions(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('TESTSECRET'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
        $this->actingAs($user);

        $component = Livewire::test(TwoFactorAuthenticationForm::class);
        /** @var TwoFactorAuthenticationForm $instance */
        $instance = $component->instance();
        $instance->showingRecoveryCodes = true;

        $actionNames = array_map(static fn (Action $action): ?string => $action->getName(), $instance->getActions());

        $this->assertContains('regenerateRecoveryCodes', $actionNames);
        $this->assertContains('hideRecoveryCodes', $actionNames);
    }

    public function test_get_form_components_returns_component_objects_when_2fa_enabled(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
        $this->actingAs($user);

        $component = Livewire::test(TwoFactorAuthenticationForm::class);

        /** @var TwoFactorAuthenticationForm $componentInstance */
        $componentInstance = $component->instance();
        // When 2FA is disabled the method returns an empty array
        // Force showingQrCode to exercise the non-empty branch.
        $componentInstance->showingQrCode = true;

        // Temporarily enable 2FA on the user model so getEnabledProperty() returns true.
        $user->forceFill(['two_factor_secret' => encrypt('fake-secret')])->save();

        /** @var Component[] $components */
        $components = (new \ReflectionMethod($componentInstance, 'getFormComponents'))
            ->invoke($componentInstance);

        $this->assertNotEmpty($components);

        foreach ($components as $item) {
            $this->assertInstanceOf(
                Component::class,
                $item,
                'getFormComponents() must return Component instances, not serialised arrays.'
            );
        }
    }

    public function test_invalid_confirmation_code_surfaces_a_code_validation_error(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-a'])),
            'two_factor_confirmed_at' => now(),
        ]);
        $this->actingAs($user);

        // The OTP provider is the non-deterministic (clock-based) boundary.
        $this->mock(TwoFactorAuthenticationProvider::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')->andReturn(false);
        });

        Livewire::test(TwoFactorAuthenticationForm::class)
            ->set('showingQrCode', true)
            ->set('showingConfirmation', true)
            ->set('code', '123456')
            ->call('confirmTwoFactorAuthentication')
            ->assertHasErrors(['code'])
            ->assertSet('showingConfirmation', true); // @phpstan-ignore-line
    }

    public function test_recovery_codes_are_empty_when_stored_payload_is_not_valid_json(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt('not-json'),
            'two_factor_confirmed_at' => now(),
        ]);
        $this->actingAs($user);

        /** @var TwoFactorAuthenticationForm $instance */
        $instance = Livewire::test(TwoFactorAuthenticationForm::class)->instance();

        $this->assertSame([], $instance->getRecoveryCodes());
    }

    public function test_recovery_codes_discard_non_string_entries(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-a', 42, 'code-b'])),
            'two_factor_confirmed_at' => now(),
        ]);
        $this->actingAs($user);

        /** @var TwoFactorAuthenticationForm $instance */
        $instance = Livewire::test(TwoFactorAuthenticationForm::class)->instance();

        $this->assertSame(['code-a', 'code-b'], $instance->getRecoveryCodes());
    }

    public function test_setup_key_is_empty_when_stored_secret_is_not_a_string(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt(['unexpected']),
            'two_factor_confirmed_at' => now(),
        ]);
        $this->actingAs($user);

        /** @var TwoFactorAuthenticationForm $instance */
        $instance = Livewire::test(TwoFactorAuthenticationForm::class)->instance();

        $this->assertSame('', $instance->getSetupKey());
    }

    public function test_enabling_two_factor_requires_the_password_when_confirmation_is_stale(): void
    {
        if (! Fortify::confirmsTwoFactorAuthentication()) {
            $this->markTestSkipped('Two factor confirmation is not enabled.');
        }

        $this->actingAs(User::factory()->create());
        $this->withSession(['auth.password_confirmed_at' => now()->subYear()->getTimestamp()]);

        Livewire::test(TwoFactorAuthenticationForm::class)
            ->call('enableTwoFactorAuthentication', 'wrong-password')
            ->assertHasErrors(['current_password'])
            ->assertSet('showingQrCode', false); // @phpstan-ignore-line
    }

    public function test_enabling_two_factor_skips_the_password_when_confirmation_is_fresh(): void
    {
        if (! Fortify::confirmsTwoFactorAuthentication()) {
            $this->markTestSkipped('Two factor confirmation is not enabled.');
        }

        $this->actingAs(User::factory()->create());
        $this->withSession(['auth.password_confirmed_at' => time()]);

        Livewire::test(TwoFactorAuthenticationForm::class)
            ->call('enableTwoFactorAuthentication', 'ignored')
            ->assertHasNoErrors()
            ->assertSet('showingQrCode', true); // @phpstan-ignore-line
    }

    /**
     * @param  list<string>  $expected
     */
    private function assertFooterActions(bool $enabled, bool $showingConfirmation, bool $showingRecoveryCodes, array $expected): void
    {
        $user = User::factory()->create($enabled ? [
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-a'])),
            'two_factor_confirmed_at' => now(),
        ] : []);
        $this->actingAs($user);

        /** @var TwoFactorAuthenticationForm $instance */
        $instance = Livewire::test(TwoFactorAuthenticationForm::class)->instance();
        $instance->showingConfirmation = $showingConfirmation;
        $instance->showingRecoveryCodes = $showingRecoveryCodes;

        $names = array_map(static fn (Action $action): ?string => $action->getName(), $instance->getActions());

        $this->assertSame($expected, $names);
    }

    public function test_footer_actions_when_two_factor_is_disabled(): void
    {
        $this->assertFooterActions(false, false, false, ['enable']);
    }

    public function test_footer_actions_when_two_factor_is_enabled_and_idle(): void
    {
        $this->assertFooterActions(true, false, false, ['showRecoveryCodes', 'disable']);
    }

    public function test_footer_actions_when_showing_recovery_codes(): void
    {
        $this->assertFooterActions(true, false, true, ['regenerateRecoveryCodes', 'hideRecoveryCodes']);
    }

    public function test_footer_actions_when_confirming_setup(): void
    {
        $this->assertFooterActions(true, true, false, ['confirm', 'cancel']);
    }

    public function test_schema_builder_creates_valid_filament_schema(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $testable = Livewire::test(TwoFactorAuthenticationForm::class);
        /** @var TwoFactorAuthenticationForm $instance */
        $instance = $testable->instance();

        $schema = $instance->form(new Schema($instance));
        $this->assertNotEmpty($schema->getComponents());
    }
}
