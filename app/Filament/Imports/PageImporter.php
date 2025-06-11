<?php

namespace App\Filament\Imports;

use App\Models\Page;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Model;

class PageImporter extends Importer
{
    protected static ?string $model = Page::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->label('ID'),
            ImportColumn::make('creator_id'),
            ImportColumn::make('title'),
            ImportColumn::make('content'),
            ImportColumn::make('status'),
            ImportColumn::make('created_at'),
            ImportColumn::make('updated_at'),
        ];
    }

    public function resolveRecord(): ?Model
    {
        /** @var Model */
        $model = app(static::getModel());
        $keyName = $model->getKeyName();
        $keyColumnName = $this->columnMap[$keyName] ?? $keyName;

        /** @var ?Page */
        $page = Page::find($this->data[$keyColumnName]);

        if (! $page) {
            $page = new Page;
        }

        return $page;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = '<strong>'.number_format($import->successful_rows).'</strong> '.str('row')->plural($import->successful_rows).' '.__('page.import_completed', ['successful_rows' => $import->successful_rows]);

        if ($failedRows = $import->getFailedRowsCount()) {
            $body .= ' <strong>'.number_format($failedRows).'</strong> '.str('row')->plural($failedRows).' '.__('page.import_failed', ['failed_rows' => $failedRows]);
        }

        return $body;
    }
}
