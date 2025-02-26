<a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
    <span class="flex items-center justify-center mb-1 rounded-md size-16">
        <x-app-logo-icon class="text-black fill-current size-16 dark:text-white" />
    </span>
    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
</a>
