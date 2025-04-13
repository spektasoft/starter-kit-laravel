@props(['locale', 'languageName', 'currentLocale' => app()->getLocale()])

<x-filament::dropdown.list.item href="{{ request()->fullUrlWithQuery(['lang' => $locale]) }}" tag="a"
    :class="$currentLocale === $locale ? 'bg-gray-100 dark:bg-white/5' : ''" :icon="$currentLocale === $locale ? 'heroicon-o-check-circle' : null" :icon-color="$currentLocale === $locale ? 'success' : null">
    {{ $languageName }}
</x-filament::dropdown.list.item>
