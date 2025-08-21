<?php

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Resources\MediaResource;
use App\Models\Media;
use Awcodes\Curator\Resources\MediaResource\EditMedia as CuratorEditMedia;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

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
        // Get the original header actions defined in the parent (Curator's) EditMedia page.
        $actions = parent::getHeaderActions();

        // Iterate through the actions to find and modify the 'preview' action.
        /** @var array<Action|ActionGroup> */
        $updatedActions = collect($actions)->map(function (Action|ActionGroup $action): Action|ActionGroup {
            // Check if the current action is the 'preview' action.
            if ($action instanceof Action && $action->getName() === 'preview') { // Check if it's an Action before getName()
                // Override its URL definition.
                // By providing a closure, the URL will be dynamically evaluated
                // when the button is clicked, using the current state of $this->record.
                return $action->url(function () {
                    /** @var Media */
                    $record = $this->record;

                    return $record->url;
                }, shouldOpenInNewTab: true);
            }

            // Return other actions as they are.
            return $action;
        })->toArray(); // Convert the collection back to an array.

        return $updatedActions;
    }
}
