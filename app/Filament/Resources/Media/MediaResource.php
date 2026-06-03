<?php

namespace App\Filament\Resources\Media;

use App\Filament\Actions\Tables\ReferenceAwareDeleteBulkAction;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\Media;
use App\Models\User;
use Awcodes\Curator\Resources\Media\MediaResource as CuratorMediaResource;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MediaResource extends CuratorMediaResource
{
    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'title', 'alt'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Media $record */
        return $record->title ?? $record->name;
    }

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
        $query = parent::getEloquentQuery()->with(['creator']);

        if (! static::canViewAll()) {
            $query->whereCreatorId(User::auth()?->id);
        }

        return $query;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    public static function getPages(): array
    {
        return [
            ...parent::getPages(),
            'index' => ListMedia::route('/'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }

    public static function table(Table $table): Table
    {
        /** @var ListMedia */
        $livewire = $table->getLivewire();

        $table = parent::table($table)
            ->toolbarActions([
                BulkActionGroup::make([
                    ReferenceAwareDeleteBulkAction::make(),
                ]),
            ])
            ->filters([
                SelectFilter::make('creator')
                    ->relationship('creator', 'name')
                    ->label(__('attributes.created_by'))
                    ->searchable()
                    ->visible(fn () => static::canViewAll()),
            ])
            ->contentGrid(function () use ($livewire) {
                if ($livewire->layoutView === 'grid') {
                    return [
                        'default' => 2,
                        'sm' => 3,
                        'md' => 3,
                        'lg' => 4,
                    ];
                }

                return null;
            })
            ->pushColumns(array_filter([
                TextColumn::make('title')
                    ->label(__('attributes.title'))
                    ->extraAttributes(['class' => $livewire->layoutView === 'grid' ? 'hidden' : ''])
                    ->searchable()
                    ->sortable(),
                static::canViewAll() ? TextColumn::make('creator.name')
                    ->hidden(fn () => $livewire->layoutView === 'grid')
                    ->icon($livewire->layoutView === 'grid' ? 'heroicon-o-user' : null)
                    ->label(__('attributes.created_by'))
                    ->searchable()
                    ->sortable() : null,
            ]))
            ->paginationPageOptions([12, 24]);

        return $table;
    }
}
