<?php

namespace App\Filament\Resources\Users\Pages;

use Filament\Actions\CreateAction;
use App\Concerns\CanUpdatePaginators;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    use CanUpdatePaginators;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
