<?php

namespace Tests\Support;

use App\Models\Permission;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Application;
use Tests\TestCase;

abstract class FilamentPanelTestCase extends TestCase
{
    public function createApplication(): Application
    {
        $application = parent::createApplication();

        $application['config']->set([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
        ]);

        $application['db']->purge('sqlite');

        return $application;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    /**
     * @param list<string> $names
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
