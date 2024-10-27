<h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
    @if ($this->enabled)
        @if ($this->showingConfirmation)
            {{ __('Finish enabling two factor authentication.') }}
        @else
            {{ __('You have enabled two factor authentication.') }}
        @endif
    @else
        {{ __('You have not enabled two factor authentication.') }}
    @endif
</h3>
