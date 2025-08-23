<?php

namespace App\Forms\Components;

use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class LocalesAwareTranslate extends Translate
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->locales(function ($record) {
            // First, ensure $record is an actual object instance.
            // In some Filament contexts (e.g., creating a new record), $record might be a class string or null.
            if (! is_object($record)) {
                // If it's not an object, we cannot call instance methods on it.
                // Return an empty array, as there's no record to get locales from.
                return [];
            }

            // Now that we know $record is an object, check if the method exists.
            if (method_exists($record, 'getPrioritizedLocales')) {
                return $record->getPrioritizedLocales();
            }

            // If the method does not exist on the object, return an empty array.
            return [];
        });
    }
}
