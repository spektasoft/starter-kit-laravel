<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\ExportResource;
use App\Models\Export;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_table_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(ExportResource::getUrl('index'))->assertSuccessful();
    }

    public function test_columns_are_displayed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Export::factory()->create([
            'user_id' => $user->id,
            'exporter' => 'App\\Exporters\\PageExporter',
            'file_name' => 'test.csv',
            'file_disk' => 'local',
            'total_rows' => 100,
            'processed_rows' => 50,
            'successful_rows' => 50,
            'completed_at' => now(),
        ]);

        $this->get(ExportResource::getUrl('index'))
            ->assertSee('Page') // From exporter column
            ->assertSee('local')
            ->assertSee('100')
            ->assertSee('50');
    }

    public function test_download_actions_are_present(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $export = Export::factory()->create([
            'user_id' => $user->id,
            'file_name' => 'test.csv',
            'file_disk' => 'local',
        ]);

        $this->get(ExportResource::getUrl('index'))
            ->assertSee(__('export.download_name', ['name' => 'CSV']))
            ->assertSee(__('export.download_name', ['name' => 'XLSX']));
    }

    public function test_get_eloquent_query_filters_results_for_non_admin_users(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Export::factory()->create(['user_id' => $user->id]);
        Export::factory()->create(['user_id' => User::factory()->create()->id]);

        $exports = ExportResource::getEloquentQuery()->get();

        $this->assertCount(1, $exports);
        $this->assertEquals($user->id, $exports->first()?->user_id);
    }
}
