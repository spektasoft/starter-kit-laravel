<?php

namespace App\Filament\Imports;

use App\Enums\Page\Status;
use App\Models\Page;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
            ImportColumn::make('status')
                ->castStateUsing(function (string $state, array $options): mixed {
                    return Status::from($state);
                }),
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
            if (Auth::check()) {
                /** @var string */
                $currentUserId = Auth::id();
                // Check if the current user has the 'viewAll' permission for the Page model
                if (Gate::check('viewAll', Page::class)) {
                    // If user can view all pages, allow setting creator_id from import data,
                    // otherwise default to current user's ID.
                    /** @var ?string */
                    $importedCreatorId = $this->data['creator_id'] ?? null;
                    $page->creator_id = $importedCreatorId ?: $currentUserId;
                } else {
                    // If user cannot view all pages, force creator_id to current user's ID.
                    $page->creator_id = $currentUserId;
                }
            }
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
