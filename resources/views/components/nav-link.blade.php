@props(['active', 'icon'])

@php
    $wrapperClass =
        $active ?? false
            ? 'relative flex items-center gap-x-3 rounded-lg px-2 py-2 outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5 bg-gray-100 dark:bg-white/5'
            : 'relative flex items-center gap-x-3 rounded-lg px-2 py-2 outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5';

    $iconClass =
        $active ?? false
            ? 'h-6 w-6 text-primary-600 dark:text-primary-400'
            : 'h-6 w-6 text-gray-400 dark:text-gray-500';

    $textClass =
        $active ?? false
            ? 'flex-1 truncate text-sm font-medium text-primary-600 dark:text-primary-400'
            : 'flex-1 truncate text-sm font-medium text-gray-700 dark:text-gray-200';
@endphp

<a {{ $attributes->merge(['class' => $wrapperClass]) }}>
    <span class="{{ $iconClass }}">
        @svg($icon)
    </span>
    <span class="{{ $textClass }}">{{ $slot }}</span>
</a>
