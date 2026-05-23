<?php

namespace App\Filament\Resources\Imports;

use App\Filament\Actions\Tables\ReferenceAwareDeleteBulkAction;
use App\Filament\Resources\Imports\Pages\ListImports;
use App\Models\Import;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ImportResource extends Resource
{
    protected static ?string $model = Import::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    public static function canViewAll(): bool
    {
        return static::can('viewAll');
    }

    /**
     * @return Builder<Import>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Import> */
        $query = parent::getEloquentQuery();

        if (! static::canViewAll()) {
            // Use a closure to group the WHERE conditions correctly
            $query->where(function (Builder $query) {
                $query->where('user_id', Auth::id())
                    ->orWhere('creator_id', Auth::id());
            });
        }

        return $query;
    }

    public static function getModelLabel(): string
    {
        return trans_choice('import.resource.model_label', 1);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Data Management');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImports::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('import.resource.model_label', 2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('importer')
                    ->label(__('import.resource.importer'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function (string $state): string {
                        $importerName = class_basename($state); // e.g., PageImporter
                        $modelName = str_replace('Importer', '', $importerName); // e.g., Page
                        $lowercasedModelName = strtolower($modelName); // e.g., page

                        return trans_choice("{$lowercasedModelName}.resource.model_label", 1);
                    }),
                TextColumn::make('file_name')
                    ->label(__('import.resource.file_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('file_path')
                    ->label(__('import.resource.file_path'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_rows')
                    ->label(__('import.resource.total_rows'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('processed_rows')
                    ->label(__('import.resource.processed_rows'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('successful_rows')
                    ->label(__('import.resource.successful_rows'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('import.resource.user'))
                    ->searchable()
                    ->sortable()
                    ->visible(static::canViewAll())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label(ucfirst(__('validation.attributes.creator')))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(static::canViewAll()),
                TextColumn::make('completed_at')
                    ->label(__('import.resource.completed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label(ucfirst(__('validation.attributes.created_at')))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->label(ucfirst(__('validation.attributes.updated_at')))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ReferenceAwareDeleteBulkAction::make(),
                ]),
            ]);
    }
}
