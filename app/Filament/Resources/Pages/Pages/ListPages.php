<?php

namespace App\Filament\Resources\Pages\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Imports\PageImporter;
use App\Filament\Resources\Pages\PageResource;
use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label(__('Import :name', ['name' => trans_choice('page.resource.model_label', 2)]))
                ->importer(PageImporter::class),
            CreateAction::make(),
        ];
    }
}
