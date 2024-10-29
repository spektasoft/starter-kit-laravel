@props([
    'method' => 'post',
])

<div class="p-4 sm:p-0">
    <form method="{{ $method }}" x-data="{ isHidden: false, isProcessing: false }" x-on:submit="if (isProcessing) $event.preventDefault()"
        x-on:form-processing-started="isProcessing = true" x-on:form-processing-finished="isProcessing = false"
        {{ $attributes }}>
        <noscript>
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('noscript.title') }}
                </x-slot>

                {{ __('noscript.message') }}
            </x-filament::section>
        </noscript>

        <div class="hidden" :class="{ 'hidden': isHidden, '': !isHidden }">
            {{ $slot }}
        </div>
    </form>
</div>
