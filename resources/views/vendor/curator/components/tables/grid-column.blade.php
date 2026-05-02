@php
    $record = $getRecord();
    $isSvg = curator()->isSvg($record->ext);
    $canViewAll = \Illuminate\Support\Facades\Gate::allows('viewAll', \App\Models\Media::class);
@endphp

<div
    {{ $attributes->merge($getExtraAttributes())->class(['curator-grid-column curator-grid-column-square relative inset-0 rounded-xl overflow-hidden']) }}>
    <div @class([
        'rounded-t-xl h-full overflow-hidden bg-gray-100 dark:bg-gray-950/50',
        'checkered' => $isSvg,
    ])>
        <x-curator::display :item="$record" :src="$record->mediumUrl" :lazy="true" icon-classes="size-24"
            x-on:click="toggleSelectedRecord('{{ $record->id }}')" @class([
                'h-full',
                'w-auto mx-auto p-2' => $isSvg,
                'object-cover w-full' => !$isSvg,
            ]) />


        <div
            class="absolute inset-x-0 bottom-0 flex items-center justify-between px-1.5 pt-10 pb-1.5 text-xs text-white bg-linear-to-t from-black/80 to-transparent gap-3">
            <div class="flex flex-col w-full gap-1">
                @if ($canViewAll)
                    <span class="flex flex-row items-center gap-1">
                        <x-filament::icon class="size-3" icon="heroicon-m-user" />
                        <p class="line-clamp-1">{{ $record->creator->name }}</p>
                    </span>
                @endif
                <div class="flex flex-row justify-between">
                    <p class="truncate">{{ $record->pretty_name }}</p>
                    <p class="shrink-0">{{ $record->size_for_humans }}</p>
                </div>
            </div>
        </div>

        <x-curator::display.info-overlay :label="$record->pretty_name" :size="$record->size" />
    </div>
</div>
