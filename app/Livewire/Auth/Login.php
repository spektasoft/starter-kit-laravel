<?php

namespace App\Livewire\Auth;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Livewire\Component;

/**
 * @property Form $form
 */
class Login extends Component implements HasForms
{
    use InteractsWithForms;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->heading(__('filament-panels::pages/auth/login.heading'))
                    ->schema([
                        TextInput::make('email')
                            ->label(__('Email'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus()
                            ->autocomplete('email')
                            ->default(old('email'))
                            ->email()
                            ->extraInputAttributes(['name' => 'email']),
                        TextInput::make('password')
                            ->label(__('Password'))
                            ->required()
                            ->password()
                            ->revealable()
                            ->hint(Route::has('password.request') ? new HtmlString(Blade::render('<x-filament::link wire:navigate href="{{ route(\'password.request\') }}" tabindex="3"> {{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
                            ->extraInputAttributes(['name' => 'password']),
                        Checkbox::make('remember')
                            ->label(__('Remember me'))
                            ->extraInputAttributes(['name' => 'remember']),
                    ])
                    ->footerActions(array_filter([
                        Action::make('login')
                            ->label(__('filament-panels::pages/auth/login.form.actions.authenticate.label'))
                            ->submit(route('login')),
                        Route::has('register') ?
                        Action::make('register')
                            ->link()
                            ->label(ucfirst(__('filament-panels::pages/auth/login.actions.register.label')))
                            ->url(route('register'))
                            ->extraAttributes(['wire:navigate' => true]) : null,
                    ]))
                    ->footerActionsAlignment(Alignment::End),
            ])
            ->statePath('data');
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
