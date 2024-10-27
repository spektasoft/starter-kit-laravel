<?php

namespace App\Livewire\EditProfile;

use App\Concerns\HasUser;
use App\Filament\Actions\PasswordConfirmationAction;
use App\Models\User;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;
use Livewire\Component;

/**
 * @property Form $form
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
     * Confirm two factor authentication for the user.
     */
    public function confirmTwoFactorAuthentication(ConfirmTwoFactorAuthentication $confirm): void
    {
        $this->resetErrorBag();

        if ($this->code === null) {
            throw ValidationException::withMessages([
                'code' => [__('The provided two factor authentication code was invalid.')],
            ])->errorBag('confirmTwoFactorAuthentication');
        }

        $confirm(Auth::user(), $this->code);

        $this->showingQrCode = false;
        $this->showingConfirmation = false;
        $this->showingRecoveryCodes = true;

        $this->dispatch('refresh-two-factor-authentication');
    }

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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->heading(__('Two Factor Authentication'))
                    ->description(__('Add additional security to your account using two factor authentication.'))
                    ->schema([
                        View::make('heading')
                            ->view('components.two-factor-authentication-form.heading'),
                        View::make('instruction')
                            ->view('components.two-factor-authentication-form.instruction'),
                        ...$this->getComponents(),
                        Actions::make($this->getFooterActions()),
                    ])
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
            /** @var string|null */
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
            return '';
        }
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

    /**
     * Enable two factor authentication for the user.
     */
    private function enableTwoFactorAuthentication(EnableTwoFactorAuthentication $enable): void
    {
        $this->resetErrorBag();

        $enable(Auth::user());

        $this->showingQrCode = true;

        if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm')) {
            $this->showingConfirmation = true;
        } else {
            $this->showingRecoveryCodes = true;
        }
    }

    /**
     * Disable two factor authentication for the user.
     */
    private function disableTwoFactorAuthentication(DisableTwoFactorAuthentication $disable): void
    {
        $this->resetErrorBag();

        $disable(Auth::user());

        $this->showingQrCode = false;
        $this->showingConfirmation = false;
        $this->showingRecoveryCodes = false;

        $this->dispatch('refresh-two-factor-authentication');
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
                        ->numeric()
                        ->autocomplete('one-time-code')
                        ->model('code')
                        ->extraAttributes(['wire:keydown.enter' => 'confirmTwoFactorAuthentication'])
                        ->extraInputAttributes(['name' => 'code'])
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
            $actions->push(PasswordConfirmationAction::make('enable')
                ->label(__('Enable'))
                ->action(function (array $data): void {
                    $this->enableTwoFactorAuthentication(
                        app(EnableTwoFactorAuthentication::class)
                    );
                }));
        } else {
            if ($this->showingRecoveryCodes) {
                $actions->push(PasswordConfirmationAction::make('regenerateRecoveryCodes')
                    ->label(__('Regenerate Recovery Codes'))
                    ->action(function (array $data) {
                        $this->regenerateRecoveryCodes(app(GenerateNewRecoveryCodes::class));
                    }));
                $actions->push(Action::make('hideRecoveryCodes')
                    ->label(__('Close'))
                    ->color('secondary')
                    ->action(function (array $data) {
                        $this->showingRecoveryCodes = false;
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
                $actions->push(PasswordConfirmationAction::make('showRecoveryCodes')
                    ->label(__('Show Recovery Codes'))
                    ->action(function (array $data) {
                        $this->showingRecoveryCodes = true;
                    }));
            }

            if ($this->showingConfirmation) {
                $actions->push(Action::make('cancel')
                    ->label(__('Cancel'))
                    ->color('secondary')
                    ->action(function (array $data) {
                        $this->disableTwoFactorAuthentication(app(DisableTwoFactorAuthentication::class));
                    }));
            } else {
                if (! $this->showingRecoveryCodes) {
                    $actions->push(PasswordConfirmationAction::make('disable')
                        ->label(__('Disable'))
                        ->color('danger')
                        ->action(fn () => $this->disableTwoFactorAuthentication(app(DisableTwoFactorAuthentication::class))));
                }
            }
        }

        /** @var Action[] */
        $arr = $actions->toArray();

        return $arr;
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate): void
    {
        $this->resetErrorBag();

        $generate(Auth::user());

        $this->showingRecoveryCodes = true;
    }
}
