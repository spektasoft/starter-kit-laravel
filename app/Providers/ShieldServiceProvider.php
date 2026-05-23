<?php

namespace App\Providers;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Pages\BasePage as Page;
use Filament\Resources\Resource;
use Filament\Widgets\Widget;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ShieldServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentShield::buildPermissionKeyUsing(
            function (string $entity, string $affix, string $subject, string $case, string $separator) {
                return match (true) {
                    is_subclass_of($entity, Resource::class) => Str::of($affix)
                        ->snake()
                        ->append('_')
                        ->append(
                            Str::of($entity)
                                ->afterLast('\\')
                                ->beforeLast('Resource')
                                ->replace('\\', '')
                                ->snake()
                                ->replace('_', '::')
                        )
                        ->toString(),
                    is_subclass_of($entity, Page::class) => Str::of('page_')
                        ->append(class_basename($entity))
                        ->toString(),
                    is_subclass_of($entity, Widget::class) => Str::of('widget_')
                        ->append(class_basename($entity))
                        ->toString(),
                    default => Str::of($affix)
                        ->append('_')
                        ->append(Str::of(class_basename($entity))->snake())
                        ->toString(),
                };
            }
        );
    }
}
