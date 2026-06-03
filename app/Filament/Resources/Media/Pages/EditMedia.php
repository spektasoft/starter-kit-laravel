<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use App\Models\Media;
use Awcodes\Curator\Resources\Media\Pages\EditMedia as CuratorEditMedia;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;

class EditMedia extends CuratorEditMedia
{
    protected static string $resource = MediaResource::class;

    /**
     * @return array<Action|ActionGroup>
     *
     * @throws Exception
     */
    public function getHeaderActions(): array
    {
        // We intentionally do NOT call parent::getHeaderActions() because
        // Curator's implementation eagerly evaluates $this->record->url at
        // registration time, before the record is resolved from the route,
        // causing: "Argument #1 ($record) must be of type Model, null given".
        //
        // Instead, we rebuild all three actions here with lazy closures.
        return [
            Action::make('save')
                ->action('save')
                ->label(trans('curator::views.panel.edit_save')),

            Action::make('preview')
                ->color('gray')
                ->url(function (): string {
                    /** @var Media $record */
                    $record = $this->record;

                    return $record->url ?? '';
                }, shouldOpenInNewTab: true)
                ->label(trans('curator::views.panel.view')),

            DeleteAction::make(),
        ];
    }
}
