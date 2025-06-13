<?php

namespace Tests\Feature\Filament\Exports;

use App\Enums\Page\Status;
use App\Filament\Exports\PageExporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PageExporterTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_returns_all_expected_columns(): void
    {
        $columns = PageExporter::getColumns();

        $this->assertCount(7, $columns); // id, creator_id, title, content, status, created_at, updated_at

        $columnNames = array_map(fn (ExportColumn $column) => $column->getName(), $columns);

        $this->assertEquals([
            'id',
            'creator_id',
            'title',
            'content',
            'status',
            'created_at',
            'updated_at',
        ], $columnNames);

        // Optionally, check labels for specific columns if they are set
        $idColumn = collect($columns)->first(fn (ExportColumn $column) => $column->getName() === 'id');
        $this->assertEquals('ID', $idColumn?->getLabel());
    }

    public function test_status_column_correctly_formats_state(): void
    {
        $columns = PageExporter::getColumns();
        $statusColumn = collect($columns)->first(fn (ExportColumn $column) => $column->getName() === 'status');
        $this->assertNotNull($statusColumn, 'Status column not found.');

        // Access the formatStateUsing closure directly from the column's internal properties
        // This is a workaround as getFormatStateUsing() is not public.
        // In a real application, you might test the output of the exporter directly.
        $reflection = new \ReflectionClass($statusColumn);
        $property = $reflection->getProperty('formatStateUsing');
        $property->setAccessible(true);
        $formatStateUsing = $property->getValue($statusColumn);

        $this->assertIsCallable($formatStateUsing);

        // Test with a specific Status enum value
        $draftStatus = Status::Draft;
        $formattedDraft = $formatStateUsing($draftStatus);
        $this->assertEquals($draftStatus->value, $formattedDraft);

        $publishedStatus = Status::Publish;
        $formattedPublished = $formatStateUsing($publishedStatus);
        $this->assertEquals($publishedStatus->value, $formattedPublished);
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
        $failedRowsCount3 = $export3->total_rows - $export3->successful_rows;
        $messageParts3 = [__('page.export_completed', ['successful_rows' => number_format($export3->successful_rows)])];
        $messageParts3[] = __('page.export_failed', ['failed_rows' => number_format($failedRowsCount3)]);
        $message3 = implode(' ', $messageParts3);
        $this->assertEquals($message3, PageExporter::getCompletedNotificationBody($export3));
    }
}
