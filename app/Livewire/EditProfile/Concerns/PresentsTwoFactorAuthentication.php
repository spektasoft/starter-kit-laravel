<?php

namespace App\Livewire\EditProfile\Concerns;

use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;

trait PresentsTwoFactorAuthentication
{
    /**
     * Determine if two factor authentication is enabled.
     */
    #[Computed]
    public function enabled(): bool
    {
        return ! empty($this->user->two_factor_secret);
    }

    /**
     * Backward-compatible getter for legacy getEnabledProperty callers.
     */
    public function getEnabledProperty(): bool
    {
        return $this->enabled();
    }

    /**
     * @return string[]
     */
    public function getRecoveryCodes(): array
    {
        if (! $this->getEnabledProperty()) {
            return [];
        }

        try {
            $two_factor_recovery_codes = $this->user->two_factor_recovery_codes;

            if ($two_factor_recovery_codes === null) {
                return [];
            }

            $decryptedCodes = decrypt($two_factor_recovery_codes);
            $codes = is_string($decryptedCodes) ? json_decode($decryptedCodes, true) : null;

            if (! is_array($codes)) {
                return [];
            }

            return array_values(array_filter($codes, static fn (mixed $code): bool => is_string($code)));
        } catch (Exception $e) {
            Log::error('Failed to decrypt 2FA recovery codes for user '.$this->user->id, [
                'exception' => $e->getMessage(),
            ]);

            Notification::make()
                ->title(__('user.two_factor.notifications.security_error_title'))
                ->body(__('user.two_factor.notifications.security_error_body'))
                ->danger()
                ->send();

            return [];
        }
    }

    public function getSetupKey(): string
    {
        if (! $this->getEnabledProperty()) {
            return '';
        }

        try {
            $two_factor_secret = $this->user->two_factor_secret;

            if ($two_factor_secret === null) {
                return '';
            }

            $setupKey = decrypt($two_factor_secret);

            return is_string($setupKey) ? $setupKey : '';
        } catch (Exception $e) {
            Log::error('Failed to decrypt 2FA secret for user '.$this->user->id, [
                'exception' => $e->getMessage(),
            ]);

            Notification::make()
                ->title(__('user.two_factor.notifications.configuration_error_title'))
                ->body(__('user.two_factor.notifications.configuration_error_body'))
                ->warning()
                ->send();

            return '';
        }
    }

    public function showTwoFactorQrCodeSvg(): string
    {
        if (! $this->getEnabledProperty()) {
            return '';
        }

        try {
            return $this->user->twoFactorQrCodeSvg();
        } catch (Exception $e) {
            return '';
        }
    }
}
