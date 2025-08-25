<?php

namespace App\Filament\Resources;

use App\Filament\Actions\Tables\ReferenceAwareDeleteBulkAction;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Awcodes\Curator\Components\Tables\CuratorColumn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Jetstream\Jetstream;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make([
                    'default' => 1,
                    'sm' => 3,
                ])->schema([
                    Forms\Components\Section::make([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->label(__('Email'))
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->label(__('Password'))
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
                        Forms\Components\Select::make('roles')
                            ->label(__('Role'))
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])->columnSpan([
                        'default' => 1,
                        'sm' => 2,
                    ]),
                    Forms\Components\Group::make(array_filter([
                        Jetstream::managesProfilePhotos() ?
                        Forms\Components\Section::make([
                            CuratorPicker::make('profile_photo_media_id')
                                ->relationship('profilePhotoMedia', 'name')
                                ->label(__('Photo'))
                                ->buttonLabel(__('Select A New Photo'))
                                ->extraAttributes(['class' => 'sm:w-fit'])
                                ->columnSpanFull(),
                        ]) : null,
                        Forms\Components\Section::make([
                            Forms\Components\DateTimePicker::make('email_verified_at')
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
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles')
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
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label(__('user.resource.email_verified_at'))
                    ->dateTime(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label(ucfirst(__('validation.attributes.created_at')))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label(ucfirst(__('validation.attributes.updated_at')))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]))
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->where('id', '!=', User::auth()?->id);

        if (! User::auth()?->isSuperUser()) {
            $query->whereNotIn(Fortify::username(), config('auth.super_users'));
        }

        return $query;
    }
}
