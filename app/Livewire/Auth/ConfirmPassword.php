<?php

namespace App\Livewire\Auth;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * @property \Filament\Schemas\Schema $form
 */
class ConfirmPassword extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->description(__('This is a secure area of the application. Please confirm your password before continuing.'))
                    ->schema([
                        TextInput::make('password')
                            ->label(__('Password'))
                            ->required()
                            ->password()
                            ->autocomplete('current-password')
                            ->autofocus()
                            ->revealable()
                            ->extraInputAttributes(['name' => 'password']),
                    ])
                    ->footerActions([
                        Action::make('confirm')
                            ->label(__('Confirm'))
                            ->submit(route('password.confirm')),
                    ])
                    ->footerActionsAlignment(Alignment::End),
            ])
            ->statePath('data');
    }

    public function render(): View
    {
        return view('livewire.auth.confirm-password');
    }
}
