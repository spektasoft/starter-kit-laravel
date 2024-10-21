@if ($errors->any())
    <div {{ $attributes }}>
        <x-filament::section>
            <x-slot name="heading">
                <div class="font-medium text-red-600 dark:text-red-400">
                    {{ __('Whoops! Something went wrong.') }}
                </div>
            </x-slot>

            <ul class="text-sm text-red-600 list-disc list-inside dark:text-red-400">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-filament::section>
    </div>
@endif
