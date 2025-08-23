<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
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

        // If there's no content yet (e.g., a new model), just return all locales.
        if ($availableLocales->isEmpty()) {
            return $allLocales;
        }

        $currentLocale = app()->getLocale();

        // If the current locale has content, move it to the front.
        if ($availableLocales->contains($currentLocale)) {
            $availableLocales = $availableLocales->filter(fn ($locale) => $locale !== $currentLocale)->prepend($currentLocale);
        }

        // Remove the available locales from the main list to avoid duplication.
        $remainingLocales = $allLocales->filter(fn ($locale) => ! $availableLocales->contains($locale));

        // Prepend the content-filled locales to the front and re-index.
        return $availableLocales->merge($remainingLocales)->values();
    }

    /**
     * Adds a where clause to the query for a translatable JSON column.
     *
     * @param  Builder<self>  $query
     * @param  string  $column  The name of the translatable column.
     * @param  string  $search  The search term.
     * @return Builder<self>
     */
    public function scopeWhereTranslatable(Builder $query, string $column, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($column, $search) {
            $locales = static::getSortedLocales();
            $search = strtolower($search);

            /** @var \Illuminate\Database\Connection $connection */
            $connection = $query->getConnection();
            $grammar = $connection->getQueryGrammar();
            $driver = $connection->getDriverName();
            $isPostgres = $driver === 'pgsql';

            foreach ($locales as $locale) {
                $wrappedColumn = $grammar->wrap($column);

                if ($isPostgres) {
                    // PostgreSQL syntax: "LOWER(column->>'locale') LIKE ?"
                    $query->orWhereRaw("LOWER({$wrappedColumn}->>?) LIKE ?", [$locale, "%{$search}%"]);
                } else {
                    // MySQL/MariaDB syntax: "LOWER(column->>'$."locale"') LIKE ?"
                    // Note the quotes around the JSON path key.
                    $query->orWhereRaw("LOWER({$wrappedColumn}->>\"$.{$locale}\") LIKE ?", ["%{$search}%"]);
                }
            }
        });
    }

    /**
     * Get the available locales by checking all translatable attributes.
     *
     * A locale is considered "available" if it has a non-empty translation
     * for at least one of the model's translatable attributes.
     *
     * @return array<int, string> An array of available locales for the model.
     */
    private function getAvailableLocales(): array
    {
        $availableLocales = [];

        foreach ($this->getTranslatableAttributes() as $attribute) {
            /** @var string $attribute */
            $translations = $this->getTranslations((string) $attribute);
            foreach ($translations as $locale => $value) {
                /** @var string $value */
                if (! empty(trim((string) $value))) {
                    $availableLocales[] = $locale;
                }
            }
        }

        return array_values(array_unique($availableLocales));
    }
}
