<?php

namespace App\Filament\Resources;

use App\Models\Media;
use App\Models\User;
use App\Utils\Authorizer;
use Awcodes\Curator\Resources\MediaResource as CuratorMediaResource;
use Awcodes\Curator\Resources\MediaResource\ListMedia;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MediaResource extends CuratorMediaResource
{
    public static function table(Table $table): Table
    {
        /** @var ListMedia */
        $livewire = $table->getLivewire();

        $table = parent::table($table)
            ->pushColumns(array_filter([
                TextColumn::make('title')
                    ->label(__('attributes.title'))
                    ->extraAttributes(['class' => $livewire->layoutView === 'grid' ? 'hidden' : ''])
                    ->searchable()
                    ->sortable(),
                Authorizer::check('viewAny', User::class) ? TextColumn::make('creator.name')
                    ->label(__('attributes.created_by'))
                    ->extraAttributes(['class' => 'my-2'])
                    ->icon($livewire->layoutView === 'grid' ? 'heroicon-o-user' : null)
                    ->badge($livewire->layoutView === 'grid')
                    ->searchable()
                    ->sortable() : null,
            ]));

        return $table;
    }

    /**
     * @return Builder<Media>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (! Authorizer::check('viewAny', User::class)) {
            $query->where('creator_id', '=', User::auth()?->id);
        }

        return $query;
    }
}
