<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class CustomPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect(Permission::$customPermissions)
            ->each(fn (string $permission) => Permission::firstOrCreate(['name' => $permission]));
    }
}
