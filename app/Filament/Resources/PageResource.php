<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\UserResource\Utils\Creator;
use App\Models\Page;
use App\Models\User;
use App\Utils\Locale;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class PageResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';

    public static function canViewAll(): bool
    {
        return static::can('viewAll');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Translate::make()
                    ->schema(function (Get $get) {
                        /** @var array<?string> */
                        $titles = $get('title');
                        $required = collect($titles)->every(fn ($item) => $item === null || trim($item) === '');

                        return [
                            Forms\Components\Textarea::make('title')
                                ->label(__('page.resource.title'))
                                ->lazy()
                                ->required($required),
                        ];
                    })
                    ->columnSpanFull()
                    ->locales(Locale::getSortedLocales())
                    ->suffixLocaleLabel(),
                Translate::make()
                    ->schema([
                        Forms\Components\Textarea::make('content')
                            ->label(__('page.resource.content')),
                    ])
                    ->columnSpanFull()
                    ->locales(Locale::getSortedLocales())
                    ->suffixLocaleLabel(),
                Creator::getComponent(static::canViewAll()),
            ]);
    }

    /**
     * @return Builder<Page>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Page> */
        $query = parent::getEloquentQuery();

        if (! static::canViewAll()) {
            $query->whereCreatorId(User::auth()?->id);
        }

        return $query;
    }

    public static function getModelLabel(): string
    {
        return trans_choice('page.resource.model_label', 1);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }

    /**
     * @return string[]
     */
    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_all',
            'view_any',
            'create',
            'update',
            'delete',
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('page.resource.model_label', 2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(array_filter([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('page.resource.title')),
                static::canViewAll() ? Tables\Columns\TextColumn::make('creator.name')
                    ->label(ucfirst(__('validation.attributes.creator')))
                    ->searchable() : null,
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
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
