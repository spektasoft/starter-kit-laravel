<?php

namespace App\Livewire\Auth;

use App\Concerns\HasUser;
use App\Models\User;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

/**
 * @property Form $form
 * @property User $user
 */
class VerifyEmail extends Component implements HasForms
{
    use HasUser;
    use InteractsWithForms;

    public function mount(): void
    {
        if ($this->user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('filament.admin.pages.dashboard', absolute: false));

            return;
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->key('actions')
                    ->heading(__('filament-panels::pages/auth/email-verification/email-verification-prompt.heading'))
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
