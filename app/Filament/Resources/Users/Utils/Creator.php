<?php

namespace App\Filament\Resources\Users\Utils;

use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;

class Creator
{
    /**
     * @return Component
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
                    ->required()
                    ->searchable(),
            ]) :
        Hidden::make('creator_id')
            ->dehydrateStateUsing(fn () => User::auth()?->id);
    }
}
