<?php

namespace App\Livewire\EditProfile;

use App\Concerns\HasUser;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Livewire\Component;

/**
 * @property Form $form
 */
class UpdatePasswordForm extends Component implements HasForms
{
    use HasUser;
    use InteractsWithForms;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->heading(__('Update Password'))
                    ->description(__('Ensure your account is using a long, random password to stay secure.'))
                    ->schema([
                        TextInput::make('current_password')
                            ->label(__('Current Password'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule('current_password'),
                        TextInput::make('password')
                            ->label(__('New Password'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule(Password::default())
                            ->same('password_confirmation')
                            ->validationAttribute(__('filament-panels::pages/auth/password-reset/reset-password.form.password.validation_attribute')),
                        TextInput::make('password_confirmation')
                            ->label(__('Confirm Password'))
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->footerActions([
                        Action::make('updatePassword')
                            ->label(__('Save'))
                            ->submit('updatePassword'),
                    ])
                    ->aside(),
            ])
            ->statePath('data');
    }

    public function mount(): void
    {
        /** @var ?array<string, mixed> */
        $data = $this->user->withoutRelations()->toArray();
        $this->form->fill($data);
    }

    public function updatePassword(UpdatesUserPasswords $updater): void
    {
        $this->resetErrorBag();

        // Call getState() outside the try-catch so Filament handles its own prefixed errors
        $state = $this->form->getState();

        try {
            $updater->update($this->user, $state);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Map Fortify Action errors (which lack the 'data.' prefix)
            throw \Illuminate\Validation\ValidationException::withMessages(
                collect($e->errors())
                    ->mapWithKeys(fn ($messages, $key) => ["data.{$key}" => $messages])
                    ->all()
            );
        }

        // Reset only the password fields in the form state
        $this->form->fill([
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        Notification::make()
            ->title(__('Saved.'))
            ->success()
            ->send();
    }
}
