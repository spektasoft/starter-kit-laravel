<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles;

use App\Filament\Actions\Tables\ReferenceAwareDeleteBulkAction;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions\BulkActionGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Table;
use Override;

class RoleResource extends ShieldRoleResource
{
    /**
     * @return array<string, string>
     */
    public static function getCustomPermissionOptions(): array
    {
        /** @var array<string, string> */
        $options = FilamentShield::getCustomPermissions(
            static::shield()->hasLocalizedPermissionLabels()
        ) ?? [];

        $options['delete-backup'] = __('role.permission.delete-backup');
        $options['download-backup'] = __('role.permission.download-backup');

        return $options;
    }

    #[Override]
    public static function getTabFormComponentForCustomPermissions(): Component
    {
        $options = static::getCustomPermissionOptions();
        $count = count($options);

        return Tab::make('custom_permissions')
            ->label(__('filament-shield::filament-shield.custom'))
            ->visible(fn (): bool => Utils::isCustomPermissionTabEnabled() && $count > 0)
            ->badge($count)
            ->schema([
                static::getCheckboxListFormComponent(
                    name: 'custom_permissions_tab',
                    options: $options,
                ),
            ]);
    }

    #[Override]
    public static function getPages(): array
    {
        $pages = parent::getPages();
        $pages['index'] = ListRoles::route('/');
        $pages['create'] = CreateRole::route('/create');
        $pages['edit'] = EditRole::route('/{record}/edit');

        return $pages;
    }

    #[Override]
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
