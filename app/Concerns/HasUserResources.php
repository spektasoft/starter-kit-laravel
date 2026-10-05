<?php

namespace App\Concerns;

use App\Models\Export;
use App\Models\Import;
use App\Models\Media;
use App\Models\Page;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasUserResources
{
    /**
     * @return HasMany<Import, $this>
     */
    public function imports(): HasMany
    {
        return $this->hasMany(Import::class, 'creator_id');
    }

    /**
     * @return HasMany<Export, $this>
     */
    public function exports(): HasMany
    {
        return $this->hasMany(Export::class, 'creator_id');
    }

    /**
     * Get all of the media for the User
     *
     * @return HasMany<Media, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'creator_id');
    }

    /**
     * @return HasMany<Page, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class, 'creator_id');
    }

    /**
     * Get a list of resources preventing account deletion.
     *
     * @return array<int, array{label: string, count: int, route: string}>
     */
    public function getBlockingResources(): array
    {
        $blockers = [];

        $checks = [
            'pages' => ['label' => trans_choice('page.resource.model_label', 2), 'route' => 'filament.admin.resources.pages.index'],
            'media' => ['label' => trans_choice('media.resource.model_label', 2), 'route' => 'filament.admin.resources.media.index'],
            'exports' => ['label' => trans_choice('export.resource.model_label', 2), 'route' => 'filament.admin.resources.exports.index'],
            'imports' => ['label' => trans_choice('import.resource.model_label', 2), 'route' => 'filament.admin.resources.imports.index'],
        ];

        foreach ($checks as $relation => $data) {
            $count = $this->{$relation}()->count();
            if ($count > 0) {
                $blockers[] = [
                    'label' => $data['label'],
                    'count' => $count,
                    'route' => route($data['route']),
                ];
            }
        }

        return $blockers;
    }

    public function isReferenced(): bool
    {
        return $this->pages()->exists() ||
            $this->media()->exists() ||
            $this->exports()->exists() ||
            $this->imports()->exists();
    }
}
