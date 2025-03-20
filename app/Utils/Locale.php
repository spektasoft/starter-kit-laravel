<?php

namespace App\Utils;

use Illuminate\Support\Collection;

class Locale
{
    /**
     * @return Collection<int, string>
     */
    public static function getSortedLocales()
    {
        $currentLocale = app()->getLocale();
        /** @var string[] */
        $locales = config('app.supported_locales', [$currentLocale]);
        $locales = collect($locales)->sortBy(fn ($locale) => $locale !== $currentLocale);

        return $locales;
    }
}
