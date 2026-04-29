<?php

namespace App\Filament\Resources\Users\Utils;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use App\Models\User;
use Filament\Forms;

class Creator
{
    /**
     * @return \Filament\Schemas\Components\Component
     */
    public static function getComponent(bool $canSelect)
    {
        return $canSelect ?
        Section::make()
            ->heading(trans_choice('user.resource.model_label', 1))
            ->schema([
                Select::make('creator_id')
                    ->label(__('attributes.created_by'))
                    ->relationship('creator', titleAttribute: 'name')
                    ->default(User::auth()?->id)
                    ->native(false)
                    ->searchable(),
            ]) :
        Hidden::make('creator_id')
            ->dehydrateStateUsing(fn () => User::auth()?->id);
    }
}
