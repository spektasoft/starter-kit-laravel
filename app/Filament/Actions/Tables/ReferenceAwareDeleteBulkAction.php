<?php

namespace App\Filament\Actions\Tables;

use App\Utils\Authorizer;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ReferenceAwareDeleteBulkAction extends DeleteBulkAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->action(function (): void {
            $this->process(static fn (Collection $records) => $records->each(function (Model $record) {
                if (Authorizer::check('delete', $record)) {
                    $record->delete();
                }
            }));

            $this->success();
        });
    }
}
