<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Actions\Tables\ReferenceAwareDeleteBulkAction;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;
use Filament\Actions\BulkActionGroup;
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
        $pages['index'] = ListRoles::route('/');
        $pages['create'] = CreateRole::route('/create');
        $pages['edit'] = EditRole::route('/{record}/edit');

        return $pages;
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->toolbarActions([
                BulkActionGroup::make([
                    ReferenceAwareDeleteBulkAction::make(),
                ]),
            ]);
    }
}
