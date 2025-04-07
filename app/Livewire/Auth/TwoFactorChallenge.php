<?php

namespace App\Livewire\Auth;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * @property Form $form
 */
class TwoFactorChallenge extends Component implements HasForms
{
    use InteractsWithForms;

    /**
     * The component's listeners.
     *
     * @var array<string, string>
     */
    protected $listeners = [
        'refresh-two-factor-challenge' => '$refresh',
    ];

    /**
     * @var bool
     */
    public $showRecovery = false;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('two-factor-authentication')
                    ->key('two-factor-authentication')
                    ->heading(__('Two Factor Authentication'))
                    ->description($this->showRecovery ? __('Please confirm access to your account by entering one of your emergency recovery codes.') : __('Please confirm access to your account by entering the authentication code provided by your authenticator application.'))
                    ->schema([
                        TextInput::make('code')
                            ->label($this->showRecovery ? __('Recovery Code') : __('Code'))
                            ->autocomplete('one-time-code')
                            ->extraInputAttributes(['name' => $this->showRecovery ? 'recovery_code' : 'code'])
                            ->required(),
                    ])
                    ->footerActions([
                        Action::make('log-in')
                            ->label(__('Login'))
                            ->submit('two-factor.login'),
                        Action::make('switch')
                            ->label($this->showRecovery ? __('Use an authentication code') : __('Use a recovery code'))
                            ->link()
                            ->action(function () {
                                $this->showRecovery = ! $this->showRecovery;
                                $this->dispatch('refresh-two-factor-challenge');
                            }),
                    ])
                    ->footerActionsAlignment(Alignment::End),
            ])
            ->statePath('data');
    }

    public function render(): View
    {
        return view('livewire.auth.two-factor-challenge');
    }
}
