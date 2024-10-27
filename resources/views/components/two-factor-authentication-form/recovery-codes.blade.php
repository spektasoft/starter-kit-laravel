<div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
    <p class="font-semibold">
        {{ __('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.') }}
    </p>
</div>

<div
    class="grid max-w-xl gap-1 px-4 py-4 mt-4 font-mono text-sm bg-gray-100 rounded-lg dark:bg-gray-900 dark:text-gray-100">
    @foreach ($this->getRecoveryCodes() as $code)
        <div>{{ $code }}</div>
    @endforeach
</div>
