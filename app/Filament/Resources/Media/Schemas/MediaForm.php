<?php

namespace App\Filament\Resources\Media\Schemas;

use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Users\Utils\Creator;
use Awcodes\Curator\Resources\Media\Schemas\MediaForm as CuratorMediaForm;
use Filament\Schemas\Components\Component;

class MediaForm extends CuratorMediaForm
{
    /**
     * @return array<Component>
     */
    public static function getAdditionalInformationFormSchema(): array
    {
        /** @var array<Component> */
        $parentComponents = parent::getAdditionalInformationFormSchema();

        return [
            ...$parentComponents,
            Creator::getComponent(MediaResource::canViewAll()),
        ];
    }
}
