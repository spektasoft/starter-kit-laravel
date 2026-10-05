<?php

namespace App\Livewire\EditProfile;

use App\Concerns\HasUser;
use App\Livewire\EditProfile\Concerns\BuildsTwoFactorFooterActions;
use App\Livewire\EditProfile\Concerns\BuildsTwoFactorFormSchema;
use App\Livewire\EditProfile\Concerns\ConfirmsTwoFactorPassword;
use App\Livewire\EditProfile\Concerns\PresentsTwoFactorAuthentication;
use App\Models\User;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;
use Livewire\Component;

/**
 * @property Schema $form
 * @property-read bool $enabled
 *
 * @method void refresh()
 */
class TwoFactorAuthenticationForm extends Component implements HasActions, HasForms
{
    use BuildsTwoFactorFooterActions;
    use BuildsTwoFactorFormSchema, InteractsWithForms {
        BuildsTwoFactorFormSchema::form insteadof InteractsWithForms;
    }
    use ConfirmsTwoFactorPassword;
    use HasUser;
    use InteractsWithActions;
    use PresentsTwoFactorAuthentication;

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

        $confirm($this->user, $this->code ?? '');

        $this->showingQrCode = false;
        $this->showingConfirmation = false;
        $this->showingRecoveryCodes = true;

        $this->dispatch('refresh-two-factor-authentication');
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
}
