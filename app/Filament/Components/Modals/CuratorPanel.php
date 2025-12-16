<?php

namespace App\Filament\Components\Modals;

use App\Models\Media;
use Awcodes\Curator\Components\Modals\CuratorPanel as BaseCuratorPanel;
use Filament\Forms\Form;

class CuratorPanel extends BaseCuratorPanel
{
    public function form(Form $form): Form
    {
        return parent::form($form)
            ->model(Media::class);
    }
}
