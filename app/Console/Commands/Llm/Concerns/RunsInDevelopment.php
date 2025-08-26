<?php

namespace App\Console\Commands\Llm\Concerns;

use Illuminate\Console\Command;

/**
 * @mixin Command
 */
trait RunsInDevelopment
{
    protected function ensureDevelopmentEnvironment(): bool
    {
        if (app()->environment('production')) {
            $this->error('This command can only be run in a development environment.');

            return false;
        }

        return true;
    }
}
