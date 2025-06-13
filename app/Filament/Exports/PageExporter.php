<?php

namespace App\Filament\Exports;

use App\Enums\Page\Status;
use App\Models\Page;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Gate;

class PageExporter extends Exporter
{
    protected static ?string $model = Page::class;

    public static function getColumns(): array
    {
        return array_filter([
            ExportColumn::make('id')
                ->label('ID'),
            Gate::check('viewAll', Page::class) ? ExportColumn::make('creator_id') : null,
            ExportColumn::make('title')
                ->state(fn (Page $record) => $record->getTranslations('title'))
                ->listAsJson(),
            ExportColumn::make('content')
                ->state(fn (Page $record) => $record->getTranslations('content'))
                ->listAsJson(),
            ExportColumn::make('status')
                ->formatStateUsing(fn (Status $state): string => static::formatStatus($state)),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ]);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = __('page.export_completed', ['successful_rows' => number_format($export->successful_rows)]);

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.__('page.export_failed', ['failed_rows' => number_format($failedRowsCount)]);
        }

        return $body;
    }

    public static function formatStatus(Status $state): string
    {
        return $state->value;
    }
}
