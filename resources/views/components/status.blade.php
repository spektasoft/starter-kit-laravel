@session('status')
    <div {{ $attributes }}>
        <x-filament::section>
            <div class="text-sm font-medium text-green-600 dark:text-green-400">
                {{ $value }}
            </div>
        </x-filament::section>
    </div>
@endsession
