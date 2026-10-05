<?php

namespace Tests\Feature\Console;

use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\PendingCommand;
use Tests\Support\FilamentPanelTestCase;

class ShieldGenerateTest extends FilamentPanelTestCase
{
    use RefreshDatabase;

    public function test_generation_persists_legacy_and_custom_permissions_without_duplicates(): void
    {
        $options = [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'permissions',
            '--no-interaction' => true,
        ];

        $command = $this->artisan('shield:generate', $options);
        $this->assertInstanceOf(PendingCommand::class, $command);
        $command->assertExitCode(0)->run();

        $expected = [
            'view_any_role',
            'update_role',
            'view_any_user',
            'page_Backups',
            'delete-backup',
            'download-backup',
        ];

        foreach ($expected as $name) {
            $this->assertDatabaseHas('permissions', [
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $count = Permission::count();

        $command = $this->artisan('shield:generate', $options);
        $this->assertInstanceOf(PendingCommand::class, $command);
        $command->assertExitCode(0)->run();

        $this->assertSame($count, Permission::count());

        foreach ($expected as $name) {
            $this->assertSame(1, Permission::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->count());
        }
    }
}
