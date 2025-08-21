<?php

namespace App\Filament\Resources;

use App\Filament\Actions\Tables\ReferenceAwareDeleteBulkAction;
use App\Filament\Resources\MediaResource\Pages;
use App\Models\Media;
use App\Models\User;
use Awcodes\Curator\Resources\MediaResource as CuratorMediaResource;
use Awcodes\Curator\Resources\MediaResource\ListMedia;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Tables;
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

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    public static function getPages(): array
    {
        return [
            ...parent::getPages(),
            'index' => Pages\ListMedia::route('/'),
            'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }

    /**
     * @return string[]
     */
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_all',
        ];
    }

    /**
     * @return array<Forms\Components\Component>
     */
    public static function getAdditionalInformationFormSchema(): array
    {
        /** @var array<Forms\Components\Component> */
        $parentComponents = parent::getAdditionalInformationFormSchema();

        return [
            ...$parentComponents,
            static::canViewAll() ? Forms\Components\Select::make('creator_id')
                ->label(__('attributes.created_by'))
                ->relationship('creator', titleAttribute: 'name')
                ->default(User::auth()?->id)
                ->native(false)
                ->searchable()
                ->required() : Forms\Components\Hidden::make('creator_id')
                ->dehydrateStateUsing(fn () => User::auth()?->id),
        ];
    }

    public static function table(Table $table): Table
    {
        /** @var ListMedia */
        $livewire = $table->getLivewire();

        $table = parent::table($table)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ReferenceAwareDeleteBulkAction::make(),
                ]),
            ])
            ->contentGrid(function () use ($livewire) {
                if ($livewire->layoutView === 'grid') {
                    return [
                        'default' => 2,
                        'sm' => 3,
                        'md' => 3,
                        'lg' => 4,
                        'xl' => 6,
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
