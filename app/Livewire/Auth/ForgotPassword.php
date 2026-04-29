<?php

namespace App\Livewire\Auth;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

/**
 * @property \Filament\Schemas\Schema $form
 */
class ForgotPassword extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('request-password')
                    ->heading(__('filament-panels::pages/auth/password-reset/request-password-reset.title'))
                    ->description(__('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.'))
                    ->schema([
                        TextInput::make('email')
                            ->label(__('Email'))
                            ->email()
                            ->required()
                            ->autocomplete()
                            ->autofocus()
                            ->extraInputAttributes(['name' => 'email']),
                    ])
                    ->footerActions(array_filter([
                        Action::make('request')
                            ->label(__('filament-panels::pages/auth/password-reset/request-password-reset.form.actions.request.label'))
                            ->submit(route('password.email')),
                        Route::has('login') ? Action::make('login')
                            ->link()
                            ->label(ucfirst(__('filament-panels::pages/auth/password-reset/request-password-reset.actions.login.label')))
                            ->url(route('login'))
                            ->extraAttributes(['wire:navigate' => true]) : null,
                    ]))
                    ->footerActionsAlignment(Alignment::Right),
            ])
            ->statePath('data');
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password');
    }
}
