<?php

namespace App\Filament\Resources\Media\Pages;

use App\Concerns\CanUpdatePaginators;
use App\Filament\Resources\Media\MediaResource;
use Awcodes\Curator\Resources\Media\Pages\ListMedia as CuratorListMedia;

class ListMedia extends CuratorListMedia
{
    use CanUpdatePaginators;

    protected static string $resource = MediaResource::class;
}
