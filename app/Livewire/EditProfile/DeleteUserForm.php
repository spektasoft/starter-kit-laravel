<?php

namespace App\Livewire\EditProfile;

use App\Concerns\HasUser;
use App\Filament\Actions\Forms\PasswordConfirmationAction;
use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\DeletesUsers;
use Livewire\Component;

/**
 * @property Form $form
 */
class DeleteUserForm extends Component implements HasForms
{
    use HasUser;
    use InteractsWithForms;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->heading(__('Delete Account'))
                    ->description(__('Permanently delete your account.'))
                    ->schema([
                        View::make('delete-account') // @phpstan-ignore-line
                            ->view('components.raw', ['html' => '<div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">'.__('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.').'</div>']),
                    ])
                    ->footerActions([
                        PasswordConfirmationAction::make('delete_account')
                            ->label(__('Delete Account'))
                            ->color('danger')
                            ->modalDescription(__('Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.'))
                            ->action(function (array $data): void {
                                /** @var string */
                                $currentPassword = $data['current_password'];
                                $this->deleteUser(
                                    app(Request::class),
                                    app(DeletesUsers::class),
                                    app(StatefulGuard::class),
                                    $currentPassword
                                );
                            }),
                    ])
                    ->aside(),
            ]);
    }

    /**
     * Delete the current user.
     */
    public function deleteUser(Request $request, DeletesUsers $deleter, StatefulGuard $auth, string $password): void
    {
        $this->resetErrorBag();

        if (! Hash::check($password, $this->user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('This password does not match our records.')],
            ]);
        }

        /** @var User */
        $freshUser = $this->user->fresh();

        $deleter->delete($freshUser);

        $auth->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        /** @var string */
        $to = config('fortify.redirects.logout') ?? '/';

        $this->redirect($to);
    }
}
