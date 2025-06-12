<?php

namespace App\Filament\Resources;

use App\Filament\Actions\Tables\ReferenceAwareDeleteBulkAction;
use App\Filament\Resources\ImportResource\Pages;
use App\Models\Import;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ImportResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Import::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

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
            $query->where('user_id', Auth::id());
        }

        return $query;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Data Management');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImports::route('/'),
        ];
    }

    /** @return array<string> */
    public static function getPermissionPrefixes(): array
    {
        return ['view_all'];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')
                    ->label(__('import.file_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('file_path')
                    ->label(__('import.file_path'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('importer')
                    ->label(__('import.importer'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_rows')
                    ->label(__('import.total_rows'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('processed_rows')
                    ->label(__('import.processed_rows'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('successful_rows')
                    ->label(__('import.successful_rows'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('import.user'))
                    ->searchable()
                    ->sortable()
                    ->visible(static::canViewAll()),
                TextColumn::make('completed_at')
                    ->label(__('import.completed_at'))
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
            ->actions([
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ReferenceAwareDeleteBulkAction::make(),
                ]),
            ]);
    }
}
