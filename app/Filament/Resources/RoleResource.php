<?php

namespace App\Filament\Resources;

use App\Filament\Actions\Tables\ReferenceAwareDeleteBulkAction;
use App\Filament\Resources\RoleResource\Pages;
use BezhanSalleh\FilamentShield\Resources\RoleResource as ShieldRoleResource;
use Filament\Tables;
use Filament\Tables\Table;

class RoleResource extends ShieldRoleResource
{
    /**
     * @return array<string, string>|null
     */
    public static function getCustomPermissionOptions(): ?array
    {
        /** @var array<string, string>|null */
        $options = parent::getCustomPermissionOptions();
        $options['delete-backup'] = __('role.permission.delete-backup');
        $options['download-backup'] = __('role.permission.download-backup');

        return $options;
    }

    public static function getPages(): array
    {
        $pages = parent::getPages();
        $pages['index'] = Pages\ListRoles::route('/');
        $pages['create'] = Pages\CreateRole::route('/create');
        $pages['edit'] = Pages\EditRole::route('/{record}/edit');

        return $pages;
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ReferenceAwareDeleteBulkAction::make(),
                ]),
            ]);
    }
}
