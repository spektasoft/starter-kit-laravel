<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class SanctumServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        RateLimiter::for('api', function (Request $request) {
            // Generate a throttle key based on authenticated user ID or IP
            $throttleKey = $request->user()
                ? $request->user()->id.'|'.$request->ip() // Authenticated user + IP
                : $request->ip(); // Guest user (only IP)

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
