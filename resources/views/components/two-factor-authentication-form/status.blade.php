<div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
    <p class="font-semibold">
        @if ($this->showingConfirmation)
            {{ __('To finish enabling two factor authentication, scan the following QR code using your phone\'s authenticator application or enter the setup key and provide the generated OTP code.') }}
        @else
            {{ __('Two factor authentication is now enabled. Scan the following QR code using your phone\'s authenticator application or enter the setup key.') }}
        @endif
    </p>
</div>
