<?php

namespace App\Filament\Resources\Media\Pages;

use App\Concerns\CanUpdatePaginators;
use Awcodes\Curator\Resources\Media\Pages\ListMedia as CuratorListMedia;

class ListMedia extends CuratorListMedia
{
    use CanUpdatePaginators;
}
