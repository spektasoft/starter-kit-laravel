<?php

namespace App\Livewire\EditProfile;

use App\Concerns\CanRestoreSession;
use App\Concerns\HasUser;
use App\Data\SessionData;
use App\Filament\Actions\Forms\PasswordConfirmationAction;
use App\Models\Session;
use Carbon\Carbon;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\View as ComponentsView;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Agent;
use Livewire\Component;

/**
 * @property Form $form
 */
class LogoutOtherBrowserSessionsForm extends Component implements HasForms
{
    use CanRestoreSession;
    use HasUser;
    use InteractsWithForms;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->key('section')
                    ->heading(__('Browser Sessions'))
                    ->description(__('Manage and log out your active sessions on other browsers and devices.'))
                    ->schema([
                        ComponentsView::make('browser-sessions') // @phpstan-ignore-line
                            ->key('browser-sessions')
                            ->view('components.browser-sessions'),
                    ])
                    ->footerActions([
                        PasswordConfirmationAction::make('logout_other_browser_sessions')
                            ->label(__('Log Out Other Browser Sessions'))
                            ->action(function (array $data): void {
                                /** @var string */
                                $currentPassword = $data['current_password'];
                                $this->logoutOtherBrowserSessions(
                                    app(StatefulGuard::class),
                                    $currentPassword
                                );
                            }),
                    ])
                    ->aside(),
            ]);
    }

    /**
     * Get the current sessions.
     *
     * @return Collection<int, SessionData>
     */
    public function getSessionsProperty()
    {
        if (config('session.driver') !== 'database') {
            /** @var array<int, SessionData> */
            $emptyArray = [];

            return collect($emptyArray);
        }

        return collect(
            Session::query()
                ->where('user_id', $this->user->getAuthIdentifier())
                ->orderBy('last_activity', 'desc')
                ->get()
        )->map(function (Session $session) {
            return new SessionData(
                $this->createAgent($session),
                $session->ip_address ?? '',
                $session->id === request()->session()->getId(),
                Carbon::createFromTimestamp($session->last_activity)->diffForHumans()
            );
        });
    }

    /**
     * Log out from other browser sessions.
     */
    public function logoutOtherBrowserSessions(StatefulGuard $guard, string $password): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        if (! ($guard instanceof SessionGuard)) {
            return;
        }

        $this->resetErrorBag();

        if (! Hash::check($password, $this->user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('This password does not match our records.')],
            ]);
        }

        $guard->logoutOtherDevices($password);

        $this->deleteOtherSessionRecords();

        $this->restoreSession();

        Notification::make()
            ->title(__('Done.'))
            ->success()
            ->send();
    }

    /**
     * Create a new agent instance from the given session.
     */
    private function createAgent(Session $session): Agent
    {
        return tap(new Agent, fn ($agent) => $agent->setUserAgent($session->user_agent ?? ''));
    }

    /**
     * Delete the other browser session records from storage.
     */
    private function deleteOtherSessionRecords(): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        Session::query()
            ->where('user_id', $this->user->getAuthIdentifier())
            ->where('id', '!=', request()->session()->getId())
            ->delete();
    }
}
