<div id="loading"
    class="fixed top-0 bottom-0 left-0 right-0 z-50 w-full h-full delay-100 -translate-x-full bg-gray-50 dark:bg-gray-950">
    <div class="flex flex-col items-center justify-center w-screen h-screen gap-2">
        <x-filament::loading-indicator class="size-5" />
        <p>{{ __('Loading…') }}</p>
    </div>
</div>
