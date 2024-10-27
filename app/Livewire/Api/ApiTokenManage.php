<?php

namespace App\Livewire\Api;

use App\Concerns\HasUser;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Jetstream;
use Livewire\Component;

/**
 * @property Form $form
 * @property Table $table
 */
class ApiTokenManage extends Component implements HasForms, HasTable
{
    use HasUser;
    use InteractsWithForms;
    use InteractsWithTable;

    /**
     * The plain text token value.
     *
     * @var string|null
     */
    public $plainTextToken = null;

    /**
     * The token name.
     *
     * @var string|null
     */
    public $name = null;

    /**
     * The token permissions.
     *
     * @var string[]
     */
    public $permissions = [];

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

    public function closeModalTokenDisplay(): void
    {
        $this->plainTextToken = null;
        $this->dispatch('close-modal', id: 'modal-token-display');
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

        $this->resetTable();
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
                            ->action('createApiToken'),
                    ])
                    ->footerActionsAlignment(Alignment::End)
                    ->aside(),
            ]);
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

    public function openModalTokenDisplay(): void
    {
        $this->dispatch('open-modal', id: 'modal-token-display');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PersonalAccessToken::query()
                ->where('tokenable_id', $this->user->id)
                ->where('tokenable_type', User::class))
            ->heading(__('Manage API Tokens'))
            ->description(__('You may delete any of your existing tokens if they are no longer needed.'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('abilities')
                    ->label(__('Permissions'))
                    ->badge(),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label(__('Last used'))
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->actions([
                /**
                 * @source https://github.com/ArtMin96/filament-jet/blob/22c19af19b02a5e694b4edea6c05a424d0a924b3/src/Http/Livewire/ApiTokensTable.php#L80
                 *
                 * @license MIT
                 */
                Action::make('permissions')
                    ->icon('heroicon-o-lock-closed')
                    ->action(function (Model $record, array $data) {
                        $record->forceFill([
                            'abilities' => Jetstream::validPermissions($data['abilities']),
                        ])->save();

                        Notification::make()
                            ->title(__('Done.'))
                            ->success()
                            ->send();
                    })
                    ->label(__('Permissions'))
                    ->modalHeading(__('API Token Permissions'))
                    ->modalWidth('2xl')
                    ->mountUsing(
                        fn (ComponentContainer $form, Model $record) => $form->fill($record->toArray())
                    )
                    ->form([
                        Forms\Components\CheckboxList::make('abilities')
                            ->label(__('Permissions'))
                            ->options(collect(Jetstream::$permissions)->mapWithKeys(function (string $permission) {
                                return [$permission => $permission];
                            }))
                            ->afterStateHydrated(function ($component, $state) {
                                $permissions = Jetstream::$permissions;

                                $tokenPermissions = collect($permissions)
                                    ->filter(function ($permission) use ($state) {
                                        return in_array($permission, $state);
                                    })
                                    ->values()
                                    ->toArray();

                                $component->state($tokenPermissions);
                            })
                            ->columns(2),
                    ])
                    ->modalFooterActionsAlignment(Alignment::End),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
