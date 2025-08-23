<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Translatable\HasTranslations;

/**
 * Provides comprehensive handling for models with translatable attributes,
 * including locale prioritization for forms.
 *
 * @mixin Model
 * @mixin HasTranslations
 */
trait HandlesTranslatableAttributes
{
    use HasLocales;
    use HasTranslatableScopes;
    use HasTranslations;

    /**
     * Get the sorted and prioritized locales for this model instance.
     *
     * The order is:
     * 1. The current application locale (if it has content).
     * 2. Other locales that have content for this record.
     * 3. All other configured system locales.
     *
     * @return Collection<int, string> The prioritized collection of locales.
     */
    public function getPrioritizedLocales(): Collection
    {
        // Start with the globally sorted locales from the HasLocales trait.
        $allLocales = static::getSortedLocales();

        // Get the locales that have content for this specific model instance.
        $availableLocales = collect($this->getAvailableLocales());

        $currentLocale = app()->getLocale();

        // Case 1: No translatable content at all (or new model instance)
        if ($availableLocales->isEmpty()) {
            // If the current locale is not already at the front, move it there.
            // This ensures new models still prioritize the current locale.
            if ($allLocales->first() !== $currentLocale && $allLocales->contains($currentLocale)) {
                return $allLocales->filter(fn ($locale) => $locale !== $currentLocale)->prepend($currentLocale)->values();
            }

            return $allLocales;
        }

        $localesWithContent = $availableLocales;
        $localesWithoutContent = collect(); // Initialize an empty collection

        // Determine if the current locale has content
        $hasCurrentLocaleContent = $localesWithContent->contains($currentLocale);

        // Build localesWithoutContent, prioritizing currentLocale if it has no content
        foreach ($allLocales as $locale) {
            if (! $localesWithContent->contains($locale)) { // If locale has no content
                if ($locale === $currentLocale && ! $hasCurrentLocaleContent) {
                    // If it's the current locale and it has no content, prepend it
                    $localesWithoutContent->prepend($locale);
                } else {
                    // Otherwise, just add it
                    $localesWithoutContent->push($locale);
                }
            }
        }

        // If current locale has content, move it to the front of localesWithContent
        if ($hasCurrentLocaleContent) {
            $localesWithContent = $localesWithContent->filter(fn ($locale) => $locale !== $currentLocale)->prepend($currentLocale);
        }

        // Combine: content locales (with current locale prioritized if it has content)
        // followed by empty locales (with current locale prioritized if it doesn't have content but is current)
        return $localesWithContent->merge($localesWithoutContent->filter(fn ($locale) => is_string($locale))->values()->all())->values();
    }

    /**
     * @return array<string>
     */
    private function getAvailableLocales(): array
    {
        /** @var array<string> */
        $attributes = $this->getTranslatableAttributes();
        $availableLocales = collect();

        foreach ($attributes as $attribute) {
            /** @var array<string, ?string> */
            $translations = $this->getTranslations($attribute);

            collect($translations)
                ->filter(fn ($value) => $value !== null && trim((string) $value) !== '' && ! preg_match('/^\s*(<[^>]+>\s*)*$/', (string) $value))
                ->keys()
                ->each(fn ($locale) => $availableLocales->add($locale));
        }

        /** @var array<string> */
        $uniqueAvailableLocales = $availableLocales->unique()->values()->all();

        return $uniqueAvailableLocales;
    }
}
