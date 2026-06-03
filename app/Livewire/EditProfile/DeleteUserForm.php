<?php

namespace App\Livewire\EditProfile;

use App\Concerns\HasUser;
use App\Filament\Actions\Forms\PasswordConfirmationAction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\DeletesUsers;
use Livewire\Component;

/**
 * @property Schema $form
 */
class DeleteUserForm extends Component implements HasActions, HasForms
{
    use HasUser;
    use InteractsWithActions;
    use InteractsWithForms;

    public function form(Schema $schema): Schema
    {
        $blockingResources = $this->user->getBlockingResources();
        $isBlocked = ! empty($blockingResources);

        return $schema
            ->components([
                Section::make('delete_section')
                    ->heading(__('Delete Account'))
                    ->description(__('Permanently delete your account.'))
                    ->schema([
                        TextEntry::make('account_deletion_warning')
                            ->label('')
                            ->state(new HtmlString(
                                '<div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">'.
                                __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.').
                                '</div>'
                            )),

                        TextEntry::make('blocking_resources_warning')
                            ->label('')
                            ->hidden(! $isBlocked)
                            ->state(function () use ($blockingResources) {
                                $listItems = collect($blockingResources)
                                    ->map(fn ($item) => sprintf(
                                        '<li><a href="%s" class="font-medium underline text-primary-600">%s (%d)</a></li>',
                                        e($item['route']),
                                        e($item['label']),
                                        $item['count']
                                    ))
                                    ->implode('');

                                return new HtmlString(sprintf(
                                    '<div class="p-4 border rounded-lg bg-danger-50 border-danger-200 dark:bg-danger-950/20 dark:border-danger-500/30">
                                        <p class="mb-2 text-sm font-bold text-danger-600 dark:text-danger-400">%s</p>
                                        <ul class="space-y-1 text-sm list-disc list-inside text-danger-600 dark:text-danger-400">%s</ul>
                                        <p class="mt-3 text-xs italic text-danger-500 dark:text-danger-500">%s</p>
                                    </div>',
                                    __('user.account_deletion_blocked_message'),
                                    $listItems,
                                    __('user.delete_resources_first_message')
                                ));
                            }),
                    ])
                    ->footerActions([
                        $this->deleteAccountAction(),
                    ])
                    ->aside(),
            ]);
    }

    public function deleteAccountAction(): Action
    {
        $isBlocked = ! empty($this->user->getBlockingResources());

        return PasswordConfirmationAction::make('delete_account')
            ->label(__('Delete Account'))
            ->color('danger')
            ->disabled($isBlocked)
            ->modalDescription(__('Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.'))
            ->action(function (array $data): void {
                $this->deleteUser(
                    app(Request::class),
                    app(DeletesUsers::class),
                    app(StatefulGuard::class)
                );
            });
    }

    /**
     * Delete the current user.
     */
    public function deleteUser(Request $request, DeletesUsers $deleter, StatefulGuard $auth): void
    {
        $this->resetErrorBag();

        /** @var User */
        $user = $this->user->fresh();

        try {
            $deleter->delete($user);
        } catch (ValidationException $e) {
            $this->addError('delete_account', $e->getMessage());

            return;
        }

        $auth->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $to = config('fortify.redirects.logout') ?? '/';

        $this->redirect($to);
    }
}
