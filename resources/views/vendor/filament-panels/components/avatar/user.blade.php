@props([
    'user' => filament()->auth()->user(),
])

@php
    $avatar = $user->getFilamentAvatarUrl();
@endphp

@if ($avatar)
    <x-filament::avatar :src="$avatar" :alt="__('filament-panels::layout.avatar.alt', ['name' => filament()->getUserName($user)])" :attributes="\Filament\Support\prepare_inherited_attributes($attributes)->class(['fi-user-avatar'])" />
@else
    @php
        $name = str(filament()->getNameForDefaultAvatar($user))
            ->trim()
            ->explode(' ')
            ->map(fn(string $segment): string => filled($segment) ? mb_substr($segment, 0, 1) : '')
            ->join('');
    @endphp
    <span
        {{ \Filament\Support\prepare_inherited_attributes($attributes)->class(['inline-flex items-center justify-center text-sm rounded-full fi-user-avatar size-8 dark:bg-primary-500 bg-primary-600']) }}>{{ $name }}</span>
@endif
