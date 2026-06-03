<?php

namespace App\Livewire\Auth;

use App\Concerns\HasUser;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

/**
 * @property Schema $form
 * @property User $user
 */
class VerifyEmail extends Component implements HasActions, HasForms
{
    use HasUser;
    use InteractsWithActions;
    use InteractsWithForms;

    public function mount(): void
    {
        if ($this->user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('filament.admin.pages.dashboard', absolute: false));

            return;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->key('actions')
                    ->heading(__('filament-panels::auth/pages/email-verification/email-verification-prompt.heading'))
                    ->description(__('Before continuing, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.'))
                    ->schema([
                        Actions::make(array_filter([
                            Action::make('resendNotification')
                                ->label(__('Resend Verification Email'))
                                ->submit(route('verification.send')),
                            Route::has('profile.show') ? Action::make('profile')
                                ->link()
                                ->label(__('Edit Profile'))
                                ->url(route('profile.show')) : null,
                        ]))->alignEnd(),
                    ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.auth.verify-email');
    }
}
