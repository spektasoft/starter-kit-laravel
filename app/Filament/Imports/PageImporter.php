<?php

namespace App\Filament\Imports;

use App\Enums\Page\Status;
use App\Models\Page;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PageImporter extends Importer
{
    protected static ?string $model = Page::class;

    public static function getColumns(): array
    {
        return array_filter([
            ImportColumn::make('id')
                ->label('ID'),
            Gate::check('viewAll', Page::class) ? ImportColumn::make('creator_id') : null,
            ImportColumn::make('title'),
            ImportColumn::make('content'),
            ImportColumn::make('status')
                ->castStateUsing(function (string $state, array $options): mixed {
                    return Status::from($state);
                }),
            ImportColumn::make('created_at'),
            ImportColumn::make('updated_at'),
        ]);
    }

    public function resolveRecord(): ?Model
    {
        /** @var User */
        $importingUser = $this->import->user;
        /** @var Model */
        $model = app(static::getModel());
        $keyName = $model->getKeyName();
        $keyColumnName = $this->columnMap[$keyName] ?? $keyName;

        /** @var ?Page */
        $page = null;

        if (isset($this->data[$keyColumnName])) {
            $page = Page::find($this->data[$keyColumnName]);
        }

        $this->data = $this->decodeTranslatableData($this->data, 'title');
        $this->data = $this->decodeTranslatableData($this->data, 'content');

        if ($page instanceof Page) {
            // Page exists, check permissions to update
            if (Gate::forUser($importingUser)->check('viewAll', Page::class)) {
                // User has permission to view all pages, update the page
                /** @var array<string, mixed> */
                $updateData = Arr::only($this->data, $page->getFillable());
                $page->fill($updateData);
            } else {
                // User doesn't have permission to view all pages, check if they own the page
                if ($page->creator->is($importingUser)) {
                    // User owns the page, update it

                    // Prevent a user from changing the owner of their own page
                    $updateData = Arr::except($this->data, ['creator_id', 'id']);
                    /** @var array<string, mixed> */
                    $updateData = Arr::only($updateData, $page->getFillable());
                    $page->fill($updateData);
                } else {
                    // User doesn't own the page and doesn't have viewAll permission, skip the row
                    return null;
                }
            }
        } else {
            // Page doesn't exist, check requirements before creating
            /** @var string */
            $currentUserId = $importingUser->id;

            // Check if the current user has the 'viewAll' permission for the Page model
            if (Gate::forUser($importingUser)->check('viewAll', Page::class)) {
                // If user can view all pages, allow setting creator_id from import data
                /** @var ?string */
                $importedCreatorId = $this->data['creator_id'] ?? null;
                if (! $importedCreatorId) {
                    // creator_id is empty, assign uploader id
                    $importedCreatorId = $currentUserId;
                }
                $creatorId = $importedCreatorId;
            } else {
                // If user cannot view all pages,
                // if creator_id is not uploader id, skip the page.
                /** @var ?string */
                $importedCreatorId = $this->data['creator_id'] ?? null;
                if ($importedCreatorId && $importedCreatorId != $currentUserId) {
                    return null;
                }
                $creatorId = $currentUserId;
            }
            // All requirements are met, create a new page
            $page = new Page;
            $this->data['creator_id'] = $creatorId;
            /** @var array<string, mixed> */
            $fillableData = Arr::only($this->data, $page->getFillable());
            $page->fill($fillableData);
        }

        return $page;
    }

    public function getValidationRules(): array
    {
        return [
            // Allow nullable for new records
            'id' => ['nullable', 'string'],

            // Add validation for creator_id if the column exists
            'creator_id' => ['required', Rule::exists('users', 'id')],

            // Title and content are handled by decodeTranslatableData,
            // but you could add basic checks here if desired.
            'title' => ['required', 'array'],
            'content' => ['required', 'array'],

            'status' => ['required', Rule::enum(Status::class)],
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        // Use the existing translation strings, which already contain HTML and pluralization.
        // The lang files may need to be adjusted for better pluralization using trans_choice.
        $body = __('page.import_completed', ['successful_rows' => number_format($import->successful_rows)]);

        if ($failedRows = $import->getFailedRowsCount()) {
            $body .= ' '.__('page.import_failed', ['failed_rows' => number_format($failedRows)]);
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function decodeTranslatableData(array $data, string $field): array
    {
        if (isset($data[$field]) && is_string($data[$field])) {
            $decoded = json_decode($data[$field], true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $data[$field] = $decoded;
            } else {
                throw new \Exception("Failed to decode JSON for field: $field. Error: ".json_last_error_msg());
            }
        }

        return $data;
    }
}
