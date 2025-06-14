<?php

namespace App\Providers;

use App\Colors\Color;
use App\Contracts\Jwt;
use App\Models\Export;
use App\Models\FailedImportRow;
use App\Models\Import;
use App\Services\AhcJwtService;
use App\Services\DeviceService;
use Exception;
use Filament\Actions\Exports\Models\Export as FilamentExport;
use Filament\Actions\Imports\Models\FailedImportRow as FilamentFailedImportRow;
use Filament\Actions\Imports\Models\Import as FilamentImport;
use Filament\Notifications\Livewire\DatabaseNotifications;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Table;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use SolutionForest\FilamentTranslateField\Facades\FilamentTranslateField;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DeviceService::class, function ($app) {
            return new DeviceService;
        });
        $this->app->singleton(Jwt::class, function (Application $app) {
            return new AhcJwtService;
        });

        $this->app->bind(FilamentExport::class, Export::class);
        $this->app->bind(FilamentImport::class, Import::class);
        $this->app->bind(FilamentFailedImportRow::class, FailedImportRow::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DatabaseNotifications::trigger('filament.notifications.database-notifications-trigger');
        FilamentColor::register([
            'primary' => Color::Vermilion,
            'secondary' => Color::WebOrange,
        ]);
        FilamentIcon::register([
            'panels::pages.dashboard.navigation-item' => 'heroicon-o-building-library',
        ]);
        FilamentView::spa();
        Table::configureUsing(function (Table $table): void {
            $table->paginationPageOptions([12, 24]);
        });

        Translatable::fallback(fallbackAny: true);

        /** @var string[]|null */
        $locales = config('app.supported_locales', null);
        if (! $locales) {
            throw new Exception('Supported locales are null.');
        }

        FilamentTranslateField::defaultLocales($locales);
    }
}
