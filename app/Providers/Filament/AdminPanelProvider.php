<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandLogo(fn () => view('components.application-mark', [
                'attributes' => new ComponentAttributeBag([
                    'class' => 'block w-auto h-9',
                ]),
            ]))
            ->colors([
                'primary' => Color::Indigo,
                'secondary' => Color::Emerald,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                EnsureEmailIsVerified::class,
                Authenticate::class,
            ])
            ->plugins([
                \Awcodes\Curator\CuratorPlugin::make(),
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                \ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin::make(),
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn () => __('navigation-menu.menu.home'))
                    ->icon('heroicon-o-home')
                    ->url(fn () => route('home')),
                MenuItem::make()
                    ->label(fn () => __('navigation-menu.menu.profile'))
                    ->icon('heroicon-o-user')
                    ->url(fn () => route('profile.show')),
                MenuItem::make()
                    ->label(fn () => __('navigation-menu.menu.api_tokens'))
                    ->icon('heroicon-o-key')
                    ->url(fn () => route('api-tokens.index')),
            ])
            ->spa()
            ->spaUrlExceptions(fn () => [
                route('home'),
                route('profile.show'),
                route('api-tokens.index'),
                url('*?lang=*'),
            ])
            ->unsavedChangesAlerts()
            ->viteTheme('resources/css/app.css');
    }
}
