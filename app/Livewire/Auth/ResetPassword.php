<?php

namespace App\Livewire\Auth;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

/**
 * @property Form $form
 */
class ResetPassword extends Component implements HasForms
{
    use InteractsWithForms;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(?string $email = null, ?string $token = null): void
    {
        $this->form->fill([
            'token' => $token ?? request()->route('token'),
            'email' => $email ?? request()->query('email'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('request-password')
                    ->heading(__('filament-panels::pages/auth/password-reset/reset-password.heading'))
                    ->schema([
                        Hidden::make('token')
                            ->extraAttributes(['name' => 'token']),
                        TextInput::make('email')
                            ->label(__('filament-panels::pages/auth/password-reset/reset-password.form.email.label'))
                            ->readOnly()
                            ->autofocus()
                            ->autocomplete('username')
                            ->extraInputAttributes(['name' => 'email']),
                        TextInput::make('password')
                            ->label(__('filament-panels::pages/auth/password-reset/reset-password.form.password.label'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule(Password::default())
                            ->same('passwordConfirmation')
                            ->validationAttribute(__('filament-panels::pages/auth/password-reset/reset-password.form.password.validation_attribute'))
                            ->extraInputAttributes(['name' => 'password']),
                        TextInput::make('passwordConfirmation')
                            ->label(__('filament-panels::pages/auth/password-reset/reset-password.form.password_confirmation.label'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->dehydrated(false)
                            ->extraInputAttributes(['name' => 'password_confirmation']),
                    ])
                    ->footerActions([
                        Action::make('resetPassword')
                            ->label(__('filament-panels::pages/auth/password-reset/reset-password.form.actions.reset.label'))
                            ->submit(route('password.update')),
                    ])
                    ->footerActionsAlignment(Alignment::Right),
            ])
            ->statePath('data');
    }

    public function render(): View
    {
        return view('livewire.auth.reset-password');
    }
}
