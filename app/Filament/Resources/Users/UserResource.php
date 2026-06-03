<?php

namespace App\Filament\Resources\Users;

use App\Filament\Actions\Tables\ReferenceAwareDeleteBulkAction;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Awcodes\Curator\Components\Tables\CuratorColumn;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Jetstream\Jetstream;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var User $record */
        return [
            'Email' => $record->email,
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var User $record */
        return $record->name ?? $record->email;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'sm' => 3,
                ])->schema([
                    Section::make([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required(),
                        TextInput::make('email')
                            ->label(__('Email'))
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label(__('Password'))
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
                        Select::make('roles')
                            ->label(__('Role'))
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])->columnSpan([
                        'default' => 1,
                        'sm' => 2,
                    ]),
                    Group::make(array_filter([
                        Jetstream::managesProfilePhotos() ?
                        Section::make([
                            CuratorPicker::make('profile_photo_media_id')
                                ->relationship('profilePhotoMedia', 'name')
                                ->label(__('Photo'))
                                ->buttonLabel(__('Select A New Photo'))
                                ->extraAttributes(['class' => 'sm:w-fit'])
                                ->columnSpanFull(),
                        ]) : null,
                        Section::make([
                            DateTimePicker::make('email_verified_at')
                                ->label(__('user.resource.email_verified_at'))
                                ->native(false),
                        ]),
                    ])),
                ]),
            ]);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('user.resource.model_label', 1);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('user.resource.model_label', 2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(array_filter([
                Jetstream::managesProfilePhotos() ?
                CuratorColumn::make('profile_photo_media_id')
                    ->label(__('Photo'))
                    ->circular()
                    ->size(32) : null,
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable(),
                TextColumn::make('roles')
                    ->label(__('Role'))
                    ->getStateUsing(function (User $record): string {
                        $roles = collect([]);
                        if ($record->isSuperUser()) {
                            $roles = $roles->merge(__('Super User'));
                        }

                        $dbRoles = collect($record->roles)
                            ->pluck('name');

                        $roles = $roles->merge($dbRoles);

                        $result = $roles
                            ->map(function ($name) {
                                /** @var string */
                                $name = $name;

                                return Str::title(str_replace('_', ' ', $name));
                            })
                            ->implode(',');

                        return $result;
                    })
                    ->badge()
                    ->separator(',')
                    ->default(''),
                TextColumn::make('email_verified_at')
                    ->label(__('user.resource.email_verified_at'))
                    ->dateTime(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label(ucfirst(__('validation.attributes.created_at')))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->label(ucfirst(__('validation.attributes.updated_at')))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]))
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ReferenceAwareDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<User> $query */
        $query = parent::getEloquentQuery()->where('id', '!=', User::auth()?->id);

        if (! User::auth()?->isSuperUser()) {
            $query->whereNotIn(Fortify::username(), config('auth.super_users'));
        }

        return $query;
    }
}
