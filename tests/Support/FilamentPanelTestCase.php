<?php

namespace Tests\Support;

use App\Models\Permission;
use App\Models\User;
use Filament\Facades\Filament;
use Tests\TestCase;

abstract class FilamentPanelTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    /**
     * @param  list<string>  $names
     */
    protected function grantPermissions(User $user, array $names): void
    {
        foreach ($names as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            $user->givePermissionTo($permission);
        }
    }
}
