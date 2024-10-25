<?php

namespace App\Filament\Resources;

use Awcodes\Curator\Resources\MediaResource as CuratorMediaResource;
use Awcodes\Curator\Resources\MediaResource\ListMedia;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MediaResource extends CuratorMediaResource
{
    public static function table(Table $table): Table
    {
        /** @var ListMedia */
        $livewire = $table->getLivewire();

        $table = parent::table($table)
            ->pushColumns([
                TextColumn::make('title')
                    ->label(__('attributes.title'))
                    ->extraAttributes(['class' => $livewire->layoutView === 'grid' ? 'hidden' : ''])
                    ->searchable()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label(__('attributes.created_by'))
                    ->extraAttributes(['class' => 'my-2'])
                    ->icon($livewire->layoutView === 'grid' ? 'heroicon-o-user' : null)
                    ->badge($livewire->layoutView === 'grid')
                    ->searchable()
                    ->sortable(),
            ]);

        return $table;
    }
}
