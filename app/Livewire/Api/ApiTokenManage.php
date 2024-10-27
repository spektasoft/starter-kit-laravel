<?php

namespace App\Livewire\Api;

use App\Concerns\HasUser;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Jetstream;
use Livewire\Component;

/**
 * @property Form $form
 */
class ApiTokenManage extends Component implements HasForms
{
    use HasUser;
    use InteractsWithForms;

    /**
     * The plain text token value.
     *
     * @var string|null
     */
    public $plainTextToken = null;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    /**
     * Mount the component.
     *
     * @return void
     */
    public function mount()
    {
        $data['permissions'] = Jetstream::$defaultPermissions;

        $this->form->fill($data);
    }

    /**
     * Create a new API token.
     *
     * @return void
     */
    public function createApiToken()
    {
        $this->resetErrorBag();

        $state = $this->form->getState();
        /** @var string */
        $name = $state['name'];
        /** @var string[] */
        $permissions = $state['permissions'];

        Validator::make([
            'name' => $name,
        ], [
            'name' => ['required', 'string', 'max:255'],
        ])->validateWithBag('createApiToken');

        $token = $this->user->createToken(
            $name,
            Jetstream::validPermissions($permissions)
        );

        $this->plainTextToken = explode('|', $token->plainTextToken, 2)[1];

        $this->form->fill();

        $this->openModalTokenDisplay();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('token_section')
                    ->heading(__('Create API Token'))
                    ->description(__('API tokens allow third-party services to authenticate with our application on your behalf.'))
                    ->schema(array_filter([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Token Name')),
                        Jetstream::hasPermissions() ? Forms\Components\CheckboxList::make('permissions')
                            ->label(__('Permissions'))
                            ->options(collect(Jetstream::$permissions)->mapWithKeys(function (string $permission) {
                                return [$permission => $permission];
                            }))
                            ->columns(2) : null,
                    ]))
                    ->footerActions([
                        Forms\Components\Actions\Action::make('create')
                            ->action('openModalTokenDisplay'),
                    ])
                    ->footerActionsAlignment(Alignment::End)
                    ->aside(),
            ])
            ->statePath('data');
    }

    public function getTokenDisplayForm(): Form
    {
        return Form::make($this)
            ->schema([
                Forms\Components\TextInput::make('plainTextToken')
                    ->label(''),
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('close')
                        ->label(__('Close'))
                        ->action('closeModalTokenDisplay'),
                ])
                    ->alignEnd(),
            ]);
    }

    public function closeModalTokenDisplay(): void
    {
        $this->plainTextToken = null;
        $this->dispatch('close-modal', id: 'modal-token-display');
    }

    public function openModalTokenDisplay(): void
    {
        $this->dispatch('open-modal', id: 'modal-token-display');
    }
}
