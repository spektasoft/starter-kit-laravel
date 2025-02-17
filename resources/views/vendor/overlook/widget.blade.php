<x-filament-widgets::widget id="overlook-widget" @class(['hidden' => !$data])>
    <x-filament::grid :default="$grid['default'] ?? 1" :sm="$grid['sm'] ?? null" :md="$grid['md'] ?? null" :lg="$grid['lg'] ?? null" :xl="$grid['xl'] ?? null"
        class="gap-4">
        @foreach ($data as $resource)
            <x-filament::grid.column>
                <x-filament::section
                    class="relative h-24 overflow-hidden overlook-card rounded-xl bg-gradient-to-tr from-gray-100 via-white to-white dark:from-gray-950 dark:to-gray-900">
                    <a wire:navigate href="{{ $resource['url'] }}"
                        class="absolute inset-0 px-3 py-2 font-medium text-gray-600 overlook-link ring-primary-500 dark:text-gray-400 group hover:ring-2 focus:ring-2"
                        @if ($this->shouldShowTooltips($resource['raw_count'])) x-data x-tooltip="'{{ $resource['raw_count'] }}'" @endif>
                        @if ($resource['icon'])
                            <x-filament::icon :icon="$resource['icon']" :size="24"
                                class="absolute left-0 w-auto h-24 transition overlook-icon top-8 text-primary-500 opacity-20 dark:opacity-20 group-hover:scale-110 group-hover:-rotate-12 group-hover:opacity-40 dark:group-hover:opacity-80" />
                        @endif
                        <span class="overlook-name">{{ $resource['name'] }}</span>
                        <span
                            class="absolute text-3xl font-bold leading-none text-gray-600 overlook-count dark:text-gray-300 bottom-3 right-4">{{ $resource['count'] }}</span>
                    </a>
                </x-filament::section>
            </x-filament::grid.column>
        @endforeach
    </x-filament::grid>
</x-filament-widgets::widget>
