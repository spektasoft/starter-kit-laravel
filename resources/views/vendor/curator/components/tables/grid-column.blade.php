@php
    $record = $getRecord();
    $isSvg = curator()->isSvg($record->ext);
    $canViewAll = \Illuminate\Support\Facades\Gate::allows('viewAll', \App\Models\Media::class);
@endphp

<div
    {{ $attributes->merge($getExtraAttributes())->class(['curator-grid-column absolute inset-0 rounded-t-xl overflow-hidden']) }}>
    <div @class([
        'rounded-t-xl h-full overflow-hidden bg-gray-100 dark:bg-gray-950/50',
        'checkered' => $isSvg,
    ])>
        <x-curator::display :item="$record" :src="$record->mediumUrl" :lazy="true" icon-classes="size-24"
            x-on:click="toggleSelectedRecord('{{ $record->id }}')" @class([
                'h-full',
                'w-auto mx-auto p-2' => $isSvg,
                'w-full' => !$isSvg,
            ]) />

        @if ($canViewAll)
            <div class="absolute inset-x-0 bottom-6 flex items-center px-1.5 pt-1.5 text-xs text-white">
                <span class="flex flex-row items-center gap-1">
                    <x-filament::icon class="size-3" icon="heroicon-m-user" />
                    <p class="line-clamp-1">{{ $record->creator->name }}</p>
                </span>
            </div>
        @endif

        <x-curator::display.info-overlay :label="$record->pretty_name" :size="$record->size" />
    </div>
</div>
