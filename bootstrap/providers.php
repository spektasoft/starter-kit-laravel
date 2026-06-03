<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\JetstreamServiceProvider;
use App\Providers\SanctumServiceProvider;
use App\Providers\ShieldServiceProvider;
use App\Queue\QueueServiceProvider;
use Artesaos\SEOTools\Providers\SEOToolsServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    FortifyServiceProvider::class,
    JetstreamServiceProvider::class,
    SanctumServiceProvider::class,
    ShieldServiceProvider::class,
    QueueServiceProvider::class,
    SEOToolsServiceProvider::class,
];
