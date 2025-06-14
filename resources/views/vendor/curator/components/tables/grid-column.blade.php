@php
    $record = $getRecord();
    $canViewAll = \Illuminate\Support\Facades\Gate::allows('viewAll', \App\Models\Media::class);
@endphp

<div
    {{ $attributes->merge($getExtraAttributes())->class(['curator-grid-column curator-grid-column-square absolute inset-0 rounded-t-xl overflow-hidden aspect-square']) }}>
    <div class="h-full overflow-hidden bg-gray-100 rounded-t-xl dark:bg-gray-950/50">
        @if (str($record->type)->contains('image'))
            <img src="{{ $record->getSignedUrl(['w' => 640, 'h' => 640, 'fit' => 'crop', 'fm' => 'webp']) }}"
                alt="{{ $record->alt }}" @class([
                    'h-full',
                    'w-auto mx-auto' => str($record->type)->contains('svg'),
                    'object-cover w-full' => !str($record->type)->contains('svg'),
                ]) />
        @else
            <x-curator::document-image :label="$record->name" icon-size="lg" :type="$record->type" :extension="$record->ext" />
        @endif
        <div
            class="absolute inset-x-0 bottom-0 flex items-center justify-between px-1.5 pt-10 pb-1.5 text-xs text-white bg-gradient-to-t from-black/80 to-transparent gap-3">
            <div class="flex flex-col w-full gap-1">
                @if ($canViewAll)
                    <div class="flex">
                        <span class="flex flex-row items-center gap-1">
                            <div>
                                <x-filament::icon class="size-3" icon="heroicon-m-user" />
                            </div>
                            <div>
                                <p class="line-clamp-1">{{ $record->creator->name }}</p>
                            </div>
                        </span>
                    </div>
                @endif
                <div class="flex flex-row justify-between">
                    <p class="truncate">{{ $record->pretty_name }}</p>
                    <p class="flex-shrink-0">{{ $record->size_for_humans }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
