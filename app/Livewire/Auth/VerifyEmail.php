<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

/**
 * @property Form $form
 */
class VerifyEmail extends Component implements HasForms
{
    use InteractsWithForms;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
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

    public function mount(): void
    {
        $user = Auth::user();

        if (! ($user instanceof User)) {
            abort(403);
        }

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('filament.admin.pages.dashboard', absolute: false));

            return;
        }

        $this->form->fill();
    }
}
