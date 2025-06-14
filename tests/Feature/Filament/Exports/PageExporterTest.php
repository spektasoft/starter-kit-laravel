<?php

namespace Tests\Feature\Filament\Exports;

use App\Enums\Page\Status;
use App\Filament\Exports\PageExporter;
use App\Models\Permission;
use App\Models\User;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PageExporterTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_status_column_correctly_formats_state(): void
    {
        $this->assertEquals(Status::Draft->value, PageExporter::formatStatus(Status::Draft));
        $this->assertEquals(Status::Publish->value, PageExporter::formatStatus(Status::Publish));
    }

    public function test_returns_correct_notification_body_for_successful_export(): void
    {
        $this->app->setLocale('en'); // Set locale to 'en'

        $export = new Export;
        $export->total_rows = 100;
        $export->successful_rows = 100;

        $body = PageExporter::getCompletedNotificationBody($export);

        $this->assertEquals(__('page.export_completed', ['successful_rows' => number_format($export->successful_rows)]), $body);
    }

    public function test_returns_correct_notification_body_for_export_with_failed_rows(): void
    {
        $this->app->setLocale('en'); // Set locale to 'en'

        $export = new Export;
        $export->total_rows = 200;
        $export->successful_rows = 100;
        $failedRowsCount = $export->total_rows - $export->successful_rows;

        $body = PageExporter::getCompletedNotificationBody($export);

        $messageParts = [__('page.export_completed', ['successful_rows' => number_format($export->successful_rows)])];

        $messageParts[] = __('page.export_failed', ['failed_rows' => number_format($failedRowsCount)]);

        $message = implode(' ', $messageParts);

        $this->assertEquals($message, $body);
    }

    public function test_handles_different_successful_and_failed_row_counts(): void
    {
        $this->app->setLocale('en'); // Set locale to 'en'

        // Case 1: Only successful rows
        $export1 = new Export;
        $export1->total_rows = 5;
        $export1->successful_rows = 5;
        $this->assertEquals(__('page.export_completed', ['successful_rows' => number_format($export1->successful_rows)]), PageExporter::getCompletedNotificationBody($export1));

        // Case 2: Successful and failed rows
        $export2 = new Export;
        $export2->total_rows = 2;
        $export2->successful_rows = 1;
        $failedRowsCount2 = $export2->total_rows - $export2->successful_rows;
        $messageParts2 = [__('page.export_completed', ['successful_rows' => number_format($export2->successful_rows)])];
        $messageParts2[] = __('page.export_failed', ['failed_rows' => number_format($failedRowsCount2)]);
        $message2 = implode(' ', $messageParts2);
        $this->assertEquals($message2, PageExporter::getCompletedNotificationBody($export2));

        // Case 3: Large numbers
        $export3 = new Export;
        $export3->total_rows = 1500000;
        $export3->successful_rows = 1000000;
    }

    public function test_creator_id_column_is_conditionally_included_based_on_permission(): void
    {
        // Create a user.
        $user = User::factory()->create();

        Permission::firstOrCreate(['name' => 'view_all_page']);
        $user->givePermissionTo('view_all_page');

        // Act as the user.
        $this->actingAs($user);

        // Get the columns.
        $columns = PageExporter::getColumns();

        // Assert that the creator_id column is present.
        $this->assertArrayHasKey(1, $columns, 'Creator ID column should be present when the user has the viewAll permission.');

        $user = User::factory()->create();

        // Act as the user.
        $this->actingAs($user);

        // Get the columns again.
        $columns = PageExporter::getColumns();

        // Assert that the creator_id column is not present.
        $this->assertArrayNotHasKey(1, $columns, 'Creator ID column should not be present when the user does not have the viewAll permission.');
    }
}
