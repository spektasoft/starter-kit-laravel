<?php

namespace App\Filament\Forms\Components;

use App\Filament\Forms\Components\RichEditor\RestrictedAttachCuratorMediaPlugin;
use App\PathGenerators\AuthenticatedUserPathGenerator;
use Filament\Forms\Components\RichEditor;
use Illuminate\Support\Facades\App;

class CuratorEnabledRichEditor extends RichEditor
{
    public static function make(?string $name = null): static
    {
        $static = parent::make($name);

        $static->fileAttachmentsDirectory(function () {
            $generator = App::make(AuthenticatedUserPathGenerator::class);

            /** @var ?string */
            $defaultDirectory = config('curator.default_directory');

            return $generator->getPath($defaultDirectory);
        })
            ->enableToolbarButtons([
                'attachCuratorMedia',
            ])->plugins([
                RestrictedAttachCuratorMediaPlugin::make(),
            ]);

        return $static;
    }
}
