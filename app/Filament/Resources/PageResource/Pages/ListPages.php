<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Imports\PageImporter;
use App\Filament\Resources\PageResource;
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
            Actions\CreateAction::make(),
        ];
    }
}
