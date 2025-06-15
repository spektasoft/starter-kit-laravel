<?php

namespace App\Filament\Actions\Tables;

use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class ReferenceAwareDeleteBulkAction extends DeleteBulkAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->action(function (): void {
            $this->process(static function (Collection $records) {
                $successfulDeletes = (int) 0;
                $failedDeletes = (int) 0;

                $records->each(function (Model $record) use (&$successfulDeletes, &$failedDeletes) {
                    if (Gate::check('delete', $record)) {
                        if ($record->delete()) {
                            $successfulDeletes++;
                        } else {
                            $failedDeletes++;
                        }
                    } else {
                        $failedDeletes++;
                    }
                });

                if ($failedDeletes > 0) {
                    Notification::make()
                        ->title(__('bulk-action.some_records_could_not_be_deleted'))
                        ->warning()
                        ->body(__('bulk-action.records_deleted_with_failures', ['success' => $successfulDeletes, 'failed' => $failedDeletes]))
                        ->persistent()
                        ->send();
                } else {
                    Notification::make()
                        ->success()
                        ->title(__('bulk-action.all_records_successfully_deleted'))
                        ->send();
                }
            });
        });
    }
}
