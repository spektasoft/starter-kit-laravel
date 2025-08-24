<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Provides query scopes for models with translatable attributes.
 *
 * @mixin Model
 * @mixin HasTranslations
 */
trait HasTranslatableScopes
{
    use HasLocales;

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

                // IMPORTANT: The PostgreSQL query below is currently untested.
                // It is critical to set up a testing environment
                // that runs the test suite against a PostgreSQL database to validate this logic.
                // Until then, this functionality for PostgreSQL users is experimental and unverified.
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
}
