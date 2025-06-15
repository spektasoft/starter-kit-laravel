<?php

/**
 * @source https://github.com/ArtMin96/filament-jet/blob/main/src/Filament/Actions/AlwaysAskPasswordConfirmationAction.php
 *
 * @license MIT
 */

namespace App\Filament\Actions\Forms;

use Filament\Forms;
use Filament\Forms\Components\Actions\Action;

class PasswordConfirmationAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation()
            ->modalHeading(__('Confirm Password'))
            ->modalDescription(
                __('For your security, please confirm your password to continue.')
            )
            ->form([
                Forms\Components\TextInput::make('current_password')
                    ->label(__('Current Password'))
                    ->required()
                    ->password()
                    ->revealable()
                    ->rule('current_password'),
            ]);
    }
}
