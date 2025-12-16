<?php

namespace App\Filament\Components\Modals;

use App\Models\Media;
use Awcodes\Curator\Components\Modals\CuratorPanel as BaseCuratorPanel;
use Filament\Forms\Form;

class CuratorPanel extends BaseCuratorPanel
{
    /**
     * Override the form method to explicitly set the model.
     *
     * Fixes issue where form model is not inferred correctly in modal context.
     * The base package may not resolve the model context properly when used
     * in a custom modal component, causing crashes when relation columns
     * are present in the custom Media model.
     */
    public function form(Form $form): Form
    {
        return parent::form($form)
            ->model(Media::class);
    }
}
