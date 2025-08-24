<?php

namespace App\Filament\Tables\Columns;

use App\Concerns\HasLocales;
use Closure;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class TranslatableTextColumn extends TextColumn
{
    use HasLocales;

    /**
     * Override the default searchable() method.
     *
     * This allows the column to behave like a normal TextColumn (not searchable
     * by default), but when ->searchable() is called, it injects our custom
     * translatable, case-insensitive search logic.
     */
    public function searchable(
        bool|array|string $condition = true,
        ?Closure $query = null,
        bool $isIndividual = false,
        bool $isGlobal = true
    ): static {
        // If the developer wants to disable search (e.g., ->searchable(false)),
        // we respect that and pass it to the parent method immediately.
        if ($condition === false) {
            return parent::searchable(false);
        }

        // If a custom search query is NOT provided by the developer, we inject our own.
        // This is the core of our enhancement.
        if ($query === null) {
            // Get the column name once, to pass it into the closure.
            $column = $this->getName();

            $query = function (Builder $query, string $search) use ($column): Builder {
                return $query->whereTranslatable($column, $search);
            };
        }

        // Now, call the original parent method with our (potentially modified) query.
        // This ensures all of Filament's underlying functionality for making a
        // column searchable is still executed.
        return parent::searchable($condition, $query, $isIndividual, $isGlobal);
    }
}
