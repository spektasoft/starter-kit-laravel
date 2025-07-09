<?php

namespace App\Console\Commands;

use Database\Seeders\CustomPermissionsSeeder;
use Illuminate\Console\Command;

class SeedCustomPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed custom permissions into the database.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Seeding custom permissions...');

        $this->call(CustomPermissionsSeeder::class);

        $this->info('Custom permissions seeded successfully!');
    }
}
