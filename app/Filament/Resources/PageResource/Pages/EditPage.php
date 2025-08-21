<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->color('gray')
                ->label(__('page.action.view'))
                ->url(fn (): string => route('pages.show', $this->record)),
            Actions\DeleteAction::make(),
        ];
    }
}
