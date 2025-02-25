<?php

namespace App\Providers;

use App\Colors\Color;
use App\Contracts\Jwt;
use App\Services\AhcJwtService;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Table;
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
            'primary' => Color::Vermilion,
            'secondary' => Color::WebOrange,
        ]);
        FilamentView::spa();
        Table::configureUsing(function (Table $table): void {
            $table->paginationPageOptions([12, 24]);
        });
    }
}
