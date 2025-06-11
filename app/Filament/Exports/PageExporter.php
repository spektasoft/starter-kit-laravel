<?php

namespace App\Filament\Exports;

use App\Enums\Page\Status;
use App\Models\Page;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PageExporter extends Exporter
{
    protected static ?string $model = Page::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('creator_id'),
            ExportColumn::make('title'),
            ExportColumn::make('content'),
            ExportColumn::make('status')
                ->formatStateUsing(fn (Status $state): string => $state->value),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = __('page.export_completed', ['successful_rows' => number_format($export->successful_rows)]);

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.__('page.export_failed', ['failed_rows' => number_format($failedRowsCount)]);
        }

        return $body;
    }
}
