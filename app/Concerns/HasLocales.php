<?php

namespace App\Concerns;

use Illuminate\Support\Collection;

trait HasLocales
{
    /**
     * @return Collection<int, string>
     */
    protected static function getSortedLocales()
    {
        $currentLocale = app()->getLocale();
        /** @var string[] */
        $locales = config('app.supported_locales', [$currentLocale]);
        $locales = collect($locales)->sortBy(fn ($locale) => $locale !== $currentLocale);

        return $locales;
    }
}
