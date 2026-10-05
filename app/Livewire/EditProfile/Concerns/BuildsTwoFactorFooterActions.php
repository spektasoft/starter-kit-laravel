<?php

namespace App\Livewire\EditProfile\Concerns;

use App\Filament\Actions\Forms\PasswordConfirmationAction;
use Filament\Actions\Action;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

trait BuildsTwoFactorFooterActions
{
    /**
     * Register footer actions with Filament's action cache so they are
     * resolvable by name via callAction() and the mounted action stack.
     *
     * @return list<Action>
     */
    public function getActions(): array
    {
        if (! $this->getEnabledProperty()) {
            return [$this->makeEnableAction()];
        }

        $actions = [];

        if ($this->showingRecoveryCodes) {
            $actions[] = $this->makeRegenerateRecoveryCodesAction();
            $actions[] = $this->makeHideRecoveryCodesAction();
        } elseif ($this->showingConfirmation) {
            $actions[] = $this->makeConfirmAction();
        } else {
            $actions[] = $this->makeShowRecoveryCodesAction();
        }

        if ($this->showingConfirmation) {
            $actions[] = $this->makeCancelAction();
        } elseif (! $this->showingRecoveryCodes) {
            $actions[] = $this->makeDisableAction();
        }

        return $actions;
    }

    protected function makeEnableAction(): Action
    {
        return $this->getProbablePasswordConfirmationAction('enable')
            ->label(__('Enable'))
            ->action(function (array $data) {
                $this->enableTwoFactorAuthentication(
                    app(EnableTwoFactorAuthentication::class),
                    $this->passwordFrom($data)
                );
            });
    }

    protected function makeRegenerateRecoveryCodesAction(): Action
    {
        return $this->getProbablePasswordConfirmationAction('regenerateRecoveryCodes')
            ->label(__('Regenerate Recovery Codes'))
            ->action(function (array $data) {
                $this->regenerateRecoveryCodes(
                    app(GenerateNewRecoveryCodes::class),
                    $this->passwordFrom($data)
                );
            });
    }

    protected function makeHideRecoveryCodesAction(): Action
    {
        return Action::make('hideRecoveryCodes')
            ->label(__('Close'))
            ->color('secondary')
            ->action(function () {
                $this->showingRecoveryCodes = false;
                $this->showingQrCode = false;
                $this->dispatch('refresh-two-factor-authentication');
            });
    }

    protected function makeConfirmAction(): Action
    {
        return Action::make('confirm')
            ->label(__('Confirm'))
            ->action(function (): void {
                $this->confirmTwoFactorAuthentication(
                    app(ConfirmTwoFactorAuthentication::class)
                );
            });
    }

    protected function makeShowRecoveryCodesAction(): Action
    {
        return $this->getProbablePasswordConfirmationAction('showRecoveryCodes')
            ->label(__('Show Recovery Codes'))
            ->action(function (array $data) {
                $this->confirmPassword($this->passwordFrom($data));
                $this->showingRecoveryCodes = true;
                $this->dispatch('refresh-two-factor-authentication');
            });
    }

    protected function makeCancelAction(): Action
    {
        return Action::make('cancel')
            ->label(__('Cancel'))
            ->color('secondary')
            ->action(fn () => $this->disableTwoFactorAuthentication(app(DisableTwoFactorAuthentication::class)));
    }

    protected function makeDisableAction(): Action
    {
        return $this->getProbablePasswordConfirmationAction('disable')
            ->label(__('Disable'))
            ->color('danger')
            ->action(function (array $data) {
                $this->disableTwoFactorAuthentication(
                    app(DisableTwoFactorAuthentication::class),
                    $this->passwordFrom($data)
                );
            });
    }

    protected function getProbablePasswordConfirmationAction(string $name): Action
    {
        if (! $this->requiresPasswordConfirmation()) {
            return Action::make($name);
        }

        return PasswordConfirmationAction::make($name);
    }

    /**
     * @param  array<mixed>  $data
     */
    protected function passwordFrom(array $data): ?string
    {
        $password = $data['current_password'] ?? null;

        return is_string($password) ? $password : null;
    }
}
