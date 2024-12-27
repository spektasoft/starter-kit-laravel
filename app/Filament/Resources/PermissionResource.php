<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Models\Permission;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PermissionResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('guard_name')
                    ->required()
                    ->maxLength(255),
            ]);
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
            'restore',
            'force_delete',
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name')),
                Tables\Columns\TextColumn::make('guard_name')
                    ->label(__('filament-shield::filament-shield.field.guard_name')),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_roles')
                    ->label(__('permission.resource.has_roles'))
                    ->trueLabel(__('Yes'))
                    ->falseLabel(__('No'))
                    ->queries(
                        true: fn (Builder $query) => $query->has('roles'),
                        false: fn (Builder $query) => $query->doesntHave('roles'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}