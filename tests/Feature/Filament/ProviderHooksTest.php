<?php

namespace Tests\Feature\Filament;

use App\Models\FailedImportRow;
use App\Models\Import;
use Filament\Actions\Imports\Models\FailedImportRow as FilamentFailedImportRow;
use Filament\Actions\Imports\Models\Import as FilamentImport;
use Tests\TestCase;

class ProviderHooksTest extends TestCase
{
    public function test_import_models_resolve_to_application_models(): void
    {
        $import = app(FilamentImport::class);
        $failedRow = app(FilamentFailedImportRow::class);

        $this->assertInstanceOf(Import::class, $import);
        $this->assertInstanceOf(FailedImportRow::class, $failedRow);
        $this->assertSame('imports', $import->getTable());
        $this->assertSame('failed_import_rows', $failedRow->getTable());
        $this->assertFalse($import->getIncrementing());
        $this->assertFalse($failedRow->getIncrementing());
    }

    public function test_post_autoload_dump_retains_the_filament_upgrade_hook(): void
    {
        $contents = file_get_contents(base_path('composer.json'));
        $this->assertIsString($contents);

        $composer = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($composer);
        $this->assertArrayHasKey('scripts', $composer);

        $scripts = $composer['scripts'];
        $this->assertIsArray($scripts);
        $this->assertArrayHasKey('post-autoload-dump', $scripts);

        $hooks = $scripts['post-autoload-dump'];
        $this->assertIsArray($hooks);
        $this->assertContains('@php artisan filament:upgrade', $hooks);
    }
}
