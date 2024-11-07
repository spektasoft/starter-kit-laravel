<?php

namespace App\Providers;

use App\Contracts\Jwt;
use App\Services\AhcJwtService;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Jwt::class, function (Application $app) {
            return new AhcJwtService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentColor::register([
            'primary' => Color::Indigo,
            'secondary' => Color::Emerald,
        ]);
    }
}
