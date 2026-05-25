<?php

namespace App\Filament\Components\Modals;

use App\Models\Media;
use Awcodes\Curator\Components\Modals\CuratorPanel as BaseCuratorPanel;
use Filament\Schemas\Schema;

class CuratorPanel extends BaseCuratorPanel
{
    public function mount(): void
    {
        parent::mount();

        // Breadcrumbs are suppressed application-wide: the directory restriction
        // feature (UserPathGenerator) ensures users only see their own files, so
        // directory navigation breadcrumbs add no value and are hidden.
        // Uses null (the package's own sentinel) rather than [] for consistency
        // with InteractsWithStorage::handleDirectoryChange().
        $this->breadcrumbs = null;
    }

    /**
     * Override the form method to explicitly set the model.
     *
     * Fixes issue where form model is not inferred correctly in modal context.
     * The base package may not resolve the model context properly when used
     * in a custom modal component, causing crashes when relation columns
     * are present in the custom Media model.
     */
    public function form(Schema $schema): Schema
    {
        return parent::form($schema)
            ->model(Media::class);
    }
}
