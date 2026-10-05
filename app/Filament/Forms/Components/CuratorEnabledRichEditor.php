<?php

namespace App\Filament\Forms\Components;

use App\Filament\Forms\Components\RichEditor\RestrictedAttachCuratorMediaPlugin;
use App\PathGenerators\AuthenticatedUserPathGenerator;
use Awcodes\Curator\Facades\Curator;
use Filament\Forms\Components\RichEditor;
use Illuminate\Support\Facades\App;

class CuratorEnabledRichEditor extends RichEditor
{
    public static function make(?string $name = null): static
    {
        $static = parent::make($name);

        static::applyCuratorConfiguration($static);

        return $static;
    }

    /**
     * Applies Curator media integration to an existing editor instance,
     * preserving any configuration already chained onto it.
     */
    public static function applyCuratorConfiguration(RichEditor $editor): RichEditor
    {
        $editor->fileAttachmentsDirectory(function () {
            $generator = App::make(AuthenticatedUserPathGenerator::class);

            /** @var ?string */
            $defaultDirectory = config('curator.default_directory');

            return $generator->getPath($defaultDirectory);
        })
            ->fileAttachmentsDisk(fn (): string => (string) Curator::getDiskName())
            ->fileAttachmentsVisibility(fn (): string => (string) Curator::getVisibility())
            ->enableToolbarButtons([
                'attachCuratorMedia',
            ])->plugins([
                RestrictedAttachCuratorMediaPlugin::make(),
            ]);

        return $editor;
    }
}
