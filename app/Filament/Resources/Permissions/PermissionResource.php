<?php

namespace App\Filament\Resources\Permissions;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Permissions\Pages\ListPermissions;
use App\Filament\Actions\Tables\ReferenceAwareDeleteBulkAction;
use App\Filament\Resources\PermissionResource\Pages;
use App\Models\Permission;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PermissionResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Permission::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-lock-closed';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function getModelLabel(): string
    {
        return trans_choice('permission.resource.model_label', 1);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-shield::filament-shield.nav.group');
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('permission.resource.model_label', 2);
    }

    /**
     * @return string[]
     */
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'delete',
            'delete_any',
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name')),
                TextColumn::make('guard_name')
                    ->label(__('filament-shield::filament-shield.field.guard_name')),
            ])
            ->filters([
                TernaryFilter::make('has_roles')
                    ->label(__('permission.resource.has_roles'))
                    ->trueLabel(__('Yes'))
                    ->falseLabel(__('No'))
                    ->queries(
                        true: fn (Builder $query) => $query->has('roles'),
                        false: fn (Builder $query) => $query->doesntHave('roles'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ReferenceAwareDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
        ];
    }
}
