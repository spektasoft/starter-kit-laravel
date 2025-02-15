<?php

namespace App\Filament\Resources;

use App\Filament\Actions\Tables\ReferenceAwareDeleteBulkAction;
use App\Models\Media;
use App\Models\User;
use Awcodes\Curator\Resources\MediaResource as CuratorMediaResource;
use Awcodes\Curator\Resources\MediaResource\ListMedia;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MediaResource extends CuratorMediaResource implements HasShieldPermissions
{
    public static function canViewAll(): bool
    {
        return static::can('viewAll');
    }

    /**
     * @return Builder<Media>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Media> */
        $query = parent::getEloquentQuery();

        if (! static::canViewAll()) {
            $query->whereCreatorId(User::auth()?->id);
        }

        return $query;
    }

    /**
     * @return string[]
     */
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_all',
            'view',
            'update',
            'delete',
            'delete_any',
            'replicate',
        ];
    }

    public static function table(Table $table): Table
    {
        /** @var ListMedia */
        $livewire = $table->getLivewire();

        $table = parent::table($table)
            ->bulkActions([
                ReferenceAwareDeleteBulkAction::make(),
            ])
            ->pushColumns(array_filter([
                TextColumn::make('title')
                    ->label(__('attributes.title'))
                    ->extraAttributes(['class' => $livewire->layoutView === 'grid' ? 'hidden' : ''])
                    ->searchable()
                    ->sortable(),
                static::canViewAll() ? TextColumn::make('creator.name')
                    ->label(__('attributes.created_by'))
                    ->extraAttributes(['class' => 'my-2'])
                    ->icon($livewire->layoutView === 'grid' ? 'heroicon-o-user' : null)
                    ->badge($livewire->layoutView === 'grid')
                    ->searchable()
                    ->sortable() : null,
            ]))
            ->paginationPageOptions([12, 24]);

        return $table;
    }
}
