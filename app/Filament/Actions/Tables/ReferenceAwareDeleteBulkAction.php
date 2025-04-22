<?php

namespace App\Filament\Actions\Tables;

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
            $this->process(static fn (Collection $records) => $records->each(function (Model $record) {
                if (Gate::check('delete', $record)) {
                    $record->delete();
                }
            }));

            $this->success();
        });
    }
}
