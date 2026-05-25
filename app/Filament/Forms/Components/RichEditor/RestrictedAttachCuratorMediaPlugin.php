<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components\RichEditor;

use Awcodes\Curator\Components\Forms\RichEditor\AttachCuratorMediaPlugin;
use Awcodes\Curator\Facades\Curator;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor as BaseRichEditor;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;

class RestrictedAttachCuratorMediaPlugin extends AttachCuratorMediaPlugin
{
    /**
     * @return array<Action>
     */
    public function getEditorActions(): array
    {
        return [
            Action::make('attachCuratorMedia')
                ->modalWidth(Width::Screen)
                ->modalHeading(__('curator::views.attach_curator_media.modal.heading'))
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalContent(fn (BaseRichEditor $component, array $arguments): View => view('curator::components.modals.curator-panel', [
                    'key' => $component->getKey(),
                    'settings' => [
                        'acceptedFileTypes' => $component->getFileAttachmentsAcceptedFileTypes(),
                        'defaultSort' => 'desc',
                        'directory' => $component->getFileAttachmentsDirectory() ?? Curator::getDirectory(),
                        'diskName' => $component->getFileAttachmentsDiskName(),
                        'imageCropAspectRatio' => Curator::getImageCropAspectRatio(),
                        'imageResizeTargetWidth' => Curator::getImageResizeTargetWidth(),
                        'imageResizeTargetHeight' => Curator::getImageResizeTargetHeight(),
                        'imageResizeMode' => Curator::getImageResizeMode(),
                        'isLimitedToDirectory' => Config::get('curator.features.directory_restriction', true),
                        'isTenantAware' => Curator::isTenantAware(),
                        'tenantOwnershipRelationshipName' => Curator::getTenantName(),
                        'isMultiple' => false,
                        'maxItems' => 1,
                        'maxSize' => $component->getFileAttachmentsMaxSize() ?? Curator::getMaxSize(),
                        'minSize' => Curator::getMinSize(),
                        'pathGenerator' => Config::get('curator.path_generator'),
                        'rules' => [],
                        'selected' => [],
                        'shouldPreserveFilenames' => Curator::shouldPreserveFilenames(),
                        'statePath' => $component->getStatePath(),
                        'context' => 'richEditor',
                        'visibility' => $component->getFileAttachmentsVisibility(),
                    ],
                ]))
                ->action(fn (): null => null),
        ];
    }
}
