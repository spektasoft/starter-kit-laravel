<?php

namespace App\Filament\Resources\MediaResource\Pages;

use App\Concerns\CanUpdatePaginators;
use Awcodes\Curator\Resources\MediaResource\ListMedia as CuratorListMedia;

class ListMedia extends CuratorListMedia
{
    use CanUpdatePaginators;
}
