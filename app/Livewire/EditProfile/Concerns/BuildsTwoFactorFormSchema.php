<?php

namespace App\Livewire\EditProfile\Concerns;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

trait BuildsTwoFactorFormSchema
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->heading(__('Two Factor Authentication'))
                    ->description(__('Add additional security to your account using two factor authentication.'))
                    ->schema(fn () => [
                        View::make('heading') // @phpstan-ignore-line
                            ->view('components.two-factor-authentication-form.heading'),
                        View::make('instruction') // @phpstan-ignore-line
                            ->view('components.two-factor-authentication-form.instruction'),
                        ...$this->getFormComponents(),
                    ])
                    ->footerActions($this->getActions())
                    ->aside(),
            ]);
    }

    /**
     * @return Component[]
     */
    protected function getFormComponents(): array
    {
        if (! $this->getEnabledProperty()) {
            return [];
        }

        /** @var Collection<int, Component> */
        $components = collect();

        if ($this->showingQrCode) {
            $components->push(
                View::make('components.two-factor-authentication-form.status'),
                View::make('components.two-factor-authentication-form.qr-code'),
                View::make('components.two-factor-authentication-form.setup-key'),
            );

            if ($this->showingConfirmation) {
                $components->push(
                    TextInput::make('code')
                        ->label(__('Code'))
                        ->required()
                        ->numeric()
                        ->length(6)
                        ->autocomplete('one-time-code')
                        ->extraAttributes(['wire:keydown.enter' => 'confirmTwoFactorAuthentication'])
                );
            }
        }

        if ($this->showingRecoveryCodes) {
            $components->push(View::make('components.two-factor-authentication-form.recovery-codes'));
        }

        /** @var Component[] */
        $arr = $components->all();

        return $arr;
    }
}
