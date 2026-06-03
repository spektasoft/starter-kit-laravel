<?php

namespace App\Livewire\Api;

use App\Concerns\CanUpdatePaginators;
use App\Concerns\HasUser;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Laravel\Jetstream\Jetstream;
use Livewire\Component;

/**
 * @property Schema $form
 * @property Table $table
 */
class ApiTokenManage extends Component implements HasActions, HasSchemas, HasTable
{
    use CanUpdatePaginators;
    use HasUser;
    use InteractsWithActions;
    use InteractsWithSchemas;
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

    public function __construct()
    {
        $this->scrollToElement = '#api-token-manage-table';
    }

    /**
     * Mount the component.
     *
     * @return void
     */
    public function mount()
    {
        /** @var string[] */
        $defaultPermissions = Jetstream::$defaultPermissions;
        $this->permissions = $defaultPermissions;
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
        ])->validate();

        $token = $this->user->createToken(
            $name,
            Jetstream::validPermissions($permissions)
        );

        $this->plainTextToken = explode('|', $token->plainTextToken, 2)[1];

        $this->form->fill();

        $this->openModalTokenDisplay();

        $this->resetTable();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->components([
                Section::make('token_section')
                    ->heading(__('Create API Token'))
                    ->description(__('API tokens allow third-party services to authenticate with our application on your behalf.'))
                    ->schema(array_filter([
                        TextInput::make('name')
                            ->label(__('Token Name')),
                        Jetstream::hasPermissions() ? CheckboxList::make('permissions')
                            ->label(__('Permissions'))
                            ->options(
                                collect($this->getPermissions())->mapWithKeys(function (string $permission) {
                                    return [$permission => $permission];
                                })
                            )
                            ->columns(2) : null,
                    ]))
                    ->footerActions([
                        Action::make('create')
                            ->action('createApiToken'),
                    ])
                    ->footerActionsAlignment(Alignment::End)
                    ->aside(),
            ]);
    }

    public function getTokenDisplayForm(): Schema
    {
        return Schema::make($this)
            ->components([
                TextInput::make('plainTextToken')
                    ->label(''),
                Actions::make([
                    Action::make('close')
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
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('abilities')
                    ->label(__('Permissions'))
                    ->badge(),
                TextColumn::make('last_used_at')
                    ->label(__('Last used'))
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    /**
                     * @source https://github.com/ArtMin96/filament-jet/blob/22c19af19b02a5e694b4edea6c05a424d0a924b3/src/Http/Livewire/ApiTokensTable.php#L80
                     *
                     * @license MIT
                     */
                    Action::make('permissions')
                        ->icon('heroicon-o-lock-closed')
                        ->action(function (Model $record, array $data) {
                            /** @var string[] */
                            $abilities = $data['abilities'] ?? [];
                            $record->forceFill([
                                'abilities' => Jetstream::validPermissions($abilities),
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
                            function (Schema $schema, PersonalAccessToken $record) {
                                $schema->fill(['abilities' => $record->abilities]);
                            })
                        ->schema([
                            CheckboxList::make('abilities')
                                ->label(__('Permissions'))
                                ->options(collect($this->getPermissions())->mapWithKeys(function (string $permission) {
                                    return [$permission => $permission];
                                }))
                                ->columns(2),
                        ])
                        ->modalFooterActionsAlignment(Alignment::End),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Render the component.
     *
     * @return View
     */
    public function render()
    {
        return view('livewire.api.api-token-manage');
    }

    /**
     * @return string[]
     */
    private function getPermissions()
    {
        /** @var string[] */
        $permissions = Jetstream::$permissions;

        return $permissions;
    }
}
