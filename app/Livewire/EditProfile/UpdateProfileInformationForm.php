<?php

namespace App\Livewire\EditProfile;

use App\Concerns\HasUser;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Livewire\Component;

/**
 * @property Form $form
 */
class UpdateProfileInformationForm extends Component implements HasForms
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
                    ->heading(__('Profile Information'))
                    ->description(__('Update your account\'s profile information and email address.'))
                    ->schema(array_filter([
                        Jetstream::managesProfilePhotos() ?
                        CuratorPicker::make('profile_photo_media_id')
                            ->relationship('profilePhotoMedia', 'name')
                            ->label(__('Photo'))
                            ->buttonLabel(__('Select A New Photo'))
                            ->extraAttributes(['class' => 'sm:w-fit']) : null,
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('Email'))
                            ->email()
                            ->required()
                            ->autocomplete()
                            ->hintAction(function () {
                                if ($this->user->hasVerifiedEmail() || ! Features::enabled(Features::emailVerification())) {
                                    return null;
                                }

                                return Action::make('sendVerification')
                                    ->label(__('Click here to re-send the verification email.'))
                                    ->action(function () {
                                        $this->user->sendEmailVerificationNotification();

                                        Notification::make()
                                            ->title(__('A new verification link has been sent to your email address.'))
                                            ->success()
                                            ->send();
                                    });
                            })
                            ->helperText(function () {
                                if ($this->user->hasVerifiedEmail() || ! Features::enabled(Features::emailVerification())) {
                                    return null;
                                }

                                return __('Your email address is unverified.');
                            })
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ]))
                    ->footerActions([
                        Action::make('updateProfileInformation')
                            ->label(__('Save'))
                            ->submit('updateProfileInformation'),
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

    /**
     * Update the user's profile information.
     */
    public function updateProfileInformation(UpdatesUserProfileInformation $updater): void
    {
        $this->resetErrorBag();

        $updater->update(
            $this->user,
            $this->form->getState()
        );

        $this->dispatch('refresh-navigation-menu');

        Notification::make()
            ->title(__('Saved.'))
            ->success()
            ->send();
    }
}
