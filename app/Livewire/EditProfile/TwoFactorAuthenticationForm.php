<?php

namespace App\Livewire\EditProfile;

use App\Concerns\HasUser;
use App\Filament\Actions\Forms\PasswordConfirmationAction;
use App\Models\User;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Component;

/**
 * @property Form $form
 *
 * @method void refresh()
 */
class TwoFactorAuthenticationForm extends Component implements HasForms
{
    use HasUser;
    use InteractsWithForms;

    /**
     * The component's listeners.
     *
     * @var array<string, string>
     */
    protected $listeners = [
        'refresh-two-factor-authentication' => '$refresh',
    ];

    /**
     * Indicates if two factor authentication QR code is being displayed.
     *
     * @var bool
     */
    public $showingQrCode = false;

    /**
     * Indicates if the two factor authentication confirmation input and button are being displayed.
     *
     * @var bool
     */
    public $showingConfirmation = false;

    /**
     * Indicates if two factor authentication recovery codes are being displayed.
     *
     * @var bool
     */
    public $showingRecoveryCodes = false;

    /**
     * The OTP code for confirming two factor authentication.
     *
     * @var string|null
     */
    public $code;

    /**
     * Mount the component.
     *
     * @return void
     */
    public function mount()
    {
        /** @var User */
        $user = Auth::user();
        if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm') &&
            is_null($user->two_factor_confirmed_at)) {
            app(DisableTwoFactorAuthentication::class)(Auth::user());
        }
    }

    /**
     * Confirm two factor authentication for the user.
     */
    public function confirmTwoFactorAuthentication(ConfirmTwoFactorAuthentication $confirm): void
    {
        $this->resetErrorBag();
        $this->form->validate();

        try {
            $confirm($this->user, $this->code ?? '');

            $this->showingQrCode = false;
            $this->showingConfirmation = false;
            $this->showingRecoveryCodes = true;

            $this->dispatch('refresh-two-factor-authentication');
        } catch (ValidationException $e) {
            throw $e;
        }
    }

    /**
     * Disable two factor authentication for the user.
     */
    public function disableTwoFactorAuthentication(DisableTwoFactorAuthentication $disable, ?string $password = null): void
    {
        $this->resetErrorBag();

        if ($this->user->two_factor_confirmed_at) {
            $this->confirmPassword($password);
        }

        $disable($this->user);

        $this->showingQrCode = false;
        $this->showingConfirmation = false;
        $this->showingRecoveryCodes = false;

        $this->dispatch('refresh-two-factor-authentication');
    }

    /**
     * Enable two factor authentication for the user.
     */
    public function enableTwoFactorAuthentication(EnableTwoFactorAuthentication $enable, ?string $password): void
    {
        $this->resetErrorBag();

        $this->confirmPassword($password);

        $enable($this->user);

        $this->showingQrCode = true;

        if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm')) {
            $this->showingConfirmation = true;
        } else {
            $this->showingRecoveryCodes = true;
        }

        $this->dispatch('refresh-two-factor-authentication');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->heading(__('Two Factor Authentication'))
                    ->description(__('Add additional security to your account using two factor authentication.'))
                    ->schema([
                        View::make('heading') // @phpstan-ignore-line
                            ->view('components.two-factor-authentication-form.heading'),
                        View::make('instruction') // @phpstan-ignore-line
                            ->view('components.two-factor-authentication-form.instruction'),
                        ...$this->getComponents(),
                    ])
                    ->footerActions($this->getFooterActions())
                    ->aside(),
            ]);
    }

    /**
     * Determine if two factor authentication is enabled.
     *
     * @return bool
     */
    public function getEnabledProperty()
    {
        return ! empty($this->user->two_factor_secret);
    }

    /**
     * @return string[]
     */
    public function getRecoveryCodes()
    {
        if (! $this->getEnabledProperty()) {
            return [];
        }

        try {
            $two_factor_recovery_codes = $this->user->two_factor_recovery_codes;

            if ($two_factor_recovery_codes === null) {
                return [];
            }

            /** @var string */
            $decryptedCodes = decrypt($two_factor_recovery_codes);
            /** @var string[] */
            $codes = json_decode($decryptedCodes, true);

            return $codes;
        } catch (\Exception $e) {
            Log::error('Failed to decrypt 2FA recovery codes for user '.$this->user->id, [
                'exception' => $e->getMessage(),
            ]);

            Notification::make()
                ->title(__('user.two_factor.notifications.security_error_title'))
                ->body(__('user.two_factor.notifications.security_error_body'))
                ->danger()
                ->send();

            return [];
        }
    }

    public function getSetupKey(): string
    {
        if (! $this->getEnabledProperty()) {
            return '';
        }

        try {
            $two_factor_secret = $this->user->two_factor_secret;

            if ($two_factor_secret === null) {
                return '';
            }

            /** @var string */
            $setupKey = decrypt($two_factor_secret);

            return $setupKey;
        } catch (\Exception $e) {
            Log::error('Failed to decrypt 2FA secret for user '.$this->user->id, [
                'exception' => $e->getMessage(),
            ]);

            Notification::make()
                ->title(__('user.two_factor.notifications.configuration_error_title'))
                ->body(__('user.two_factor.notifications.configuration_error_body'))
                ->warning()
                ->send();

            return '';
        }
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate, ?string $password): void
    {
        $this->resetErrorBag();

        $this->confirmPassword($password);

        $generate($this->user);

        $this->showingRecoveryCodes = true;
    }

    public function showTwoFactorQrCodeSvg(): string
    {
        if (! $this->getEnabledProperty()) {
            return '';
        }

        try {
            return $this->user->twoFactorQrCodeSvg();
        } catch (\Exception $e) {
            return '';
        }
    }

    private function confirmPassword(?string $password): void
    {
        /** @var int */
        $confirmedAt = session('auth.password_confirmed_at', 0);
        /** @var int */
        $timeout = config('auth.password_timeout', 10800);

        if (! Fortify::confirmsTwoFactorAuthentication() || (time() - $confirmedAt) < $timeout) {
            return;
        }

        if (! $password || ! Hash::check($password, $this->user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('This password does not match our records.')],
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);
    }

    private function getProbablePasswordConfirmationAction(string $name): Action
    {
        /** @var int */
        $confirmedAt = session('auth.password_confirmed_at', 0);
        /** @var int */
        $timeout = config('auth.password_timeout', 10800);

        if (! Fortify::confirmsTwoFactorAuthentication() || (time() - $confirmedAt) < $timeout) {
            return Action::make($name);
        }

        return PasswordConfirmationAction::make($name);
    }

    /**
     * @return \Filament\Forms\Components\Component[]
     */
    private function getComponents()
    {
        if (! $this->getEnabledProperty()) {
            return [];
        }

        /** @var Collection<int, \Filament\Forms\Components\Component> */
        $components = collect();

        if ($this->showingQrCode) {
            $components->push(
                View::make('components.two-factor-authentication-form.status'),
                View::make('components.two-factor-authentication-form.qr-code'),
                View::make('components.two-factor-authentication-form.setup-key'),
            );

            if ($this->showingConfirmation) {
                $components->push(
                    TextInput::make('code')
                        ->label(__('Code'))
                        ->required()
                        ->numeric()
                        ->length(6)
                        ->autocomplete('one-time-code')
                        ->model('code')
                        ->extraAttributes(['wire:keydown.enter' => 'confirmTwoFactorAuthentication'])
                );
            }
        }

        if ($this->showingRecoveryCodes) {
            $components->push(View::make('components.two-factor-authentication-form.recovery-codes'));
        }

        /** @var \Filament\Forms\Components\Component[] */
        $arr = $components->toArray();

        return $arr;
    }

    /**
     * @return Action[]
     */
    private function getFooterActions()
    {
        /** @var Collection<int, Action> */
        $actions = collect();

        if (! $this->getEnabledProperty()) {
            $actions->push($this->getProbablePasswordConfirmationAction('enable')
                ->label(__('Enable'))
                ->action(
                    function (array $data) {
                        /** @var ?string */
                        $currentPassword = $data['current_password'] ?? null; // Safe access
                        $this->enableTwoFactorAuthentication(
                            app(EnableTwoFactorAuthentication::class),
                            $currentPassword
                        );
                    }
                )
            );
        } else {
            if ($this->showingRecoveryCodes) {
                $actions->push($this->getProbablePasswordConfirmationAction('regenerateRecoveryCodes')
                    ->label(__('Regenerate Recovery Codes'))
                    ->action(
                        function (array $data) {
                            /** @var ?string */
                            $currentPassword = $data['current_password'] ?? null; // Safe access
                            $this->regenerateRecoveryCodes(
                                app(GenerateNewRecoveryCodes::class),
                                $currentPassword
                            );
                        }
                    )
                );
                $actions->push(Action::make('hideRecoveryCodes')
                    ->label(__('Close'))
                    ->color('secondary')
                    ->action(function () {
                        $this->showingRecoveryCodes = false;
                        $this->showingQrCode = false;
                        $this->dispatch('refresh-two-factor-authentication');
                    }));
            } elseif ($this->showingConfirmation) {
                $actions->push(Action::make('confirm')
                    ->label(__('Confirm'))
                    ->action(function (array $data): void {
                        $this->confirmTwoFactorAuthentication(
                            app(ConfirmTwoFactorAuthentication::class)
                        );
                    }));
            } else {
                $actions->push($this->getProbablePasswordConfirmationAction('showRecoveryCodes')
                    ->label(__('Show Recovery Codes'))
                    ->action(
                        function (array $data) {
                            /** @var ?string */
                            $currentPassword = $data['current_password'] ?? null; // Safe access
                            $this->confirmPassword(
                                $currentPassword
                            );
                            $this->showingRecoveryCodes = true;
                            $this->dispatch('refresh-two-factor-authentication');
                        }
                    )
                );
            }

            if ($this->showingConfirmation) {
                $actions->push(Action::make('cancel')
                    ->label(__('Cancel'))
                    ->color('secondary')
                    ->action(fn () => $this->disableTwoFactorAuthentication(app(DisableTwoFactorAuthentication::class))));
            } else {
                if (! $this->showingRecoveryCodes) {
                    $actions->push($this->getProbablePasswordConfirmationAction('disable')
                        ->label(__('Disable'))
                        ->color('danger')
                        ->action(
                            function (array $data) {
                                /** @var ?string */
                                $currentPassword = $data['current_password'] ?? null; // Safe access
                                $this->disableTwoFactorAuthentication(
                                    app(DisableTwoFactorAuthentication::class),
                                    $currentPassword
                                );
                            }
                        )
                    );
                }
            }
        }

        /** @var Action[] */
        $arr = $actions->toArray();

        return $arr;
    }
}
