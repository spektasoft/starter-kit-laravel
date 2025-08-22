<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\ImportResource;
use App\Filament\Resources\ImportResource\Pages\ListImports;
use App\Models\Import;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ImportResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_table_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(ImportResource::getUrl('index'))->assertSuccessful();
    }

    public function test_columns_are_displayed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Import::factory()->create([
            'user_id' => $user->id,
            'importer' => 'App\\Importers\\PageImporter',
            'file_name' => 'import_test.csv',
            'file_path' => '/tmp/import_test.csv',
            'total_rows' => 150,
            'processed_rows' => 100,
            'successful_rows' => 90,
            'completed_at' => now(),
        ]);

        // Mock the trans_choice function for the importer column
        // This is necessary because the resource uses trans_choice which might not be available
        // or correctly configured in a feature test without a full application context.
        \Illuminate\Support\Facades\Lang::shouldReceive('trans_choice')
            ->with('page.resource.model_label', 1)
            ->andReturn('Page');

        $this->get(ImportResource::getUrl('index'))
            ->assertSee('Page') // From importer column
            ->assertSee('import_test.csv')
            ->assertSee('150')
            ->assertSee('100')
            ->assertSee('90');
    }

    public function test_get_eloquent_query_filters_results_for_non_view_all_users(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $otherUser = User::factory()->create();

        // Ensure the user does NOT have 'view_all_import' permission
        // No permission assignment needed here as it's the default state

        Import::factory()->create(['user_id' => $user->id, 'creator_id' => $user->id]);
        Import::factory()->create(['user_id' => $otherUser->id, 'creator_id' => $otherUser->id]); // Another user's import

        $imports = ImportResource::getEloquentQuery()->get();

        $this->assertCount(1, $imports);
        $this->assertEquals($user->id, $imports->first()?->user_id);
    }

    public function test_get_eloquent_query_does_not_filter_results_for_view_all_users(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Assign 'view_all_import' permission to the user
        Permission::firstOrCreate(['name' => 'view_all_import']);
        $user->givePermissionTo('view_all_import');

        Import::factory()->create(['user_id' => $user->id]);
        Import::factory()->create(['user_id' => User::factory()->create()->id]); // Another user's import

        $imports = ImportResource::getEloquentQuery()->get();

        $this->assertCount(2, $imports);
    }

    public function test_user_name_column_visibility_for_view_all_users(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Assign 'view_all_import' permission to the user
        Permission::firstOrCreate(['name' => 'view_all_import']);
        $user->givePermissionTo('view_all_import');

        Import::factory()->create(['user_id' => $user->id]);

        Livewire::test(ListImports::class)
            ->assertTableColumnVisible('user.name');
    }

    public function test_user_name_column_hidden_for_non_view_all_users(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Ensure the user does NOT have 'view_all_import' permission
        // No permission assignment needed here as it's the default state

        Import::factory()->create(['user_id' => $user->id]);

        Livewire::test(ListImports::class)
            ->assertTableColumnHidden('user.name');
    }

    public function test_importer_column_format_state_using(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Import::factory()->create([
            'user_id' => $user->id,
            'importer' => 'App\\Importers\\ProductImporter',
        ]);

        // Mock the trans_choice function for the importer column
        \Illuminate\Support\Facades\Lang::shouldReceive('trans_choice')
            ->with('product.resource.model_label', 1)
            ->andReturn('Product');

        $this->get(ImportResource::getUrl('index'))
            ->assertSee('Product');
    }
}
