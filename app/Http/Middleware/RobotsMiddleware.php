<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Spatie\RobotsMiddleware\RobotsMiddleware as SpatieRobotsMiddleware;

class RobotsMiddleware extends SpatieRobotsMiddleware
{
    /**
     * Determine if the response should be indexed.
     */
    protected function shouldIndex(Request $request): string|bool
    {
        if (app()->environment('production')) {
            return ! $request->is('admin', 'admin/*');
        }

        return false;
    }
}
