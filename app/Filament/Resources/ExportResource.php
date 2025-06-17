<?php

namespace App\Filament\Resources;

use App\Filament\Actions\Tables\ReferenceAwareDeleteBulkAction;
use App\Filament\Resources\ExportResource\Pages;
use App\Models\Export;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ExportResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Export::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    public static function canViewAll(): bool
    {
        return static::can('viewAll');
    }

    /**
     * @return Builder<Export>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Export> */
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
        return trans_choice('export.resource.model_label', 1);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Data Management');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExports::route('/'),
        ];
    }

    /**
     * @return string[]
     */
    public static function getPermissionPrefixes(): array
    {
        return ['view_all'];
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('export.resource.model_label', 2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('exporter')
                    ->label(__('export.resource.exporter'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function (string $state): string {
                        $exporterName = class_basename($state); // e.g., PageExporter
                        $modelName = str_replace('Exporter', '', $exporterName); // e.g., Page
                        $lowercasedModelName = strtolower($modelName); // e.g., page

                        return trans_choice("{$lowercasedModelName}.resource.model_label", 1);
                    }),
                Tables\Columns\TextColumn::make('file_name')
                    ->label(__('export.resource.file_name'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('file_disk')
                    ->label(__('export.resource.file_disk'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_rows')
                    ->label(__('export.resource.total_rows'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_rows')
                    ->label(__('export.resource.processed_rows'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('successful_rows')
                    ->label(__('export.resource.successful_rows'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('export.resource.user'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(static::canViewAll()),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label(ucfirst(__('validation.attributes.creator')))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(static::canViewAll()),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label(__('export.resource.completed_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label(ucfirst(__('validation.attributes.created_at')))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label(ucfirst(__('validation.attributes.updated_at')))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('download_csv')
                        ->label(__('export.resource.download_name', ['name' => 'CSV']))
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (Export $record): string => route('filament.exports.download', ['export' => $record->id, 'format' => 'csv']))
                        ->openUrlInNewTab()
                        ->visible(fn (Export $record) => filled($record->file_name) && filled($record->file_disk)),
                    Tables\Actions\Action::make('download_xlsx')
                        ->label(__('export.resource.download_name', ['name' => 'XLSX']))
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (Export $record): string => route('filament.exports.download', ['export' => $record->id, 'format' => 'xlsx']))
                        ->openUrlInNewTab()
                        ->visible(fn (Export $record) => filled($record->file_name) && filled($record->file_disk)),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ReferenceAwareDeleteBulkAction::make(),
                ]),
            ]);
    }
}
