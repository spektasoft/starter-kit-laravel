<?php

namespace App\Filament\Resources\UserResource\Utils;

use App\Models\User;
use Filament\Forms;

class Creator
{
    /**
     * @return Forms\Components\Component
     */
    public static function getComponent(bool $canSelect)
    {
        return $canSelect ?
        Forms\Components\Section::make()
            ->heading(trans_choice('user.resource.model_label', 1))
            ->schema([
                Forms\Components\Select::make('creator_id')
                    ->label(__('attributes.created_by'))
                    ->relationship('creator', titleAttribute: 'name')
                    ->default(User::auth()?->id)
                    ->native(false)
                    ->searchable(),
            ]) :
        Forms\Components\Hidden::make('creator_id')
            ->dehydrateStateUsing(fn () => User::auth()?->id);
    }
}
