<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Laravel\Jetstream\Features;
use Livewire\Component;

/**
 * @property Form $form
 */
class Register extends Component implements HasForms
{
    use InteractsWithForms;

    public function mount(): void
    {
        $this->form->fill();
    }

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function form(Form $form): Form
    {
        if (Features::hasTermsAndPrivacyPolicyFeature()) {
            $termsOfService = Blade::render("<x-filament::link target=\"_blank\" href=\"{{ route('terms.show') }}\">{{ __('Terms of Service') }}</x-filament::link>");
            $privacyPolicy = Blade::render("<x-filament::link target=\"_blank\" href=\"{{ route('policy.show') }}\">{{ __('Privacy Policy') }}</x-filament::link>");
        }

        return $form
            ->schema([
                Section::make()
                    ->heading(__('filament-panels::pages/auth/register.heading'))
                    ->schema(array_filter([
                        TextInput::make('name')
                            ->label(__('filament-panels::pages/auth/register.form.name.label'))
                            ->default(old('name'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus()
                            ->autocomplete('name')
                            ->extraInputAttributes(['name' => 'name']),
                        TextInput::make('email')
                            ->label(__('filament-panels::pages/auth/register.form.email.label'))
                            ->default(old('email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(User::class)
                            ->autocomplete('email')
                            ->extraInputAttributes(['name' => 'email']),
                        TextInput::make('password')
                            ->label(__('filament-panels::pages/auth/register.form.password.label'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->autocomplete('new-password')
                            ->rule(Password::default())
                            ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                            ->same('passwordConfirmation')
                            ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute'))
                            ->extraInputAttributes(['name' => 'password']),
                        TextInput::make('passwordConfirmation')
                            ->label(__('filament-panels::pages/auth/register.form.password_confirmation.label'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->autocomplete('new-password')
                            ->dehydrated(false)
                            ->extraInputAttributes(['name' => 'password_confirmation']),
                        Features::hasTermsAndPrivacyPolicyFeature() ? Checkbox::make('terms')
                            ->label(new HtmlString(__('I agree to the :terms_of_service and :privacy_policy', [
                                'terms_of_service' => $termsOfService,
                                'privacy_policy' => $privacyPolicy,
                            ])))
                            ->required()
                            ->extraInputAttributes(['name' => 'terms']) : null,
                    ]))
                    ->footerActions(array_filter([
                        Action::make('register')
                            ->label(__('filament-panels::pages/auth/register.form.actions.register.label'))
                            ->submit(route('register')),
                        Route::has('login') ? Action::make('login')
                            ->link()
                            ->label(__('Already registered?'))
                            ->url(route('login'))
                            ->extraAttributes(['wire:navigate' => true]) : null,
                    ]))
                    ->footerActionsAlignment(Alignment::End),
            ])
            ->statePath('data');
    }

    public function render(): View
    {
        return view('livewire.auth.register');
    }
}
