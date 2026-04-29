<?php

namespace App\Filament\Resources\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Radio;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportBulkAction;
use App\Enums\Page\Status;
use App\Filament\Actions\Tables\ReferenceAwareDeleteBulkAction;
use App\Filament\Exports\PageExporter;
use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\Users\Utils\Creator;
use App\Filament\Tables\Columns\TranslatableTextColumn;
use App\Forms\Components\LocalesAwareTranslate;
use App\Models\Page;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PageResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Page::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document';

    public static function canViewAll(): bool
    {
        return static::can('viewAll');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'sm' => 6,
                ])->schema([
                    Group::make([
                        LocalesAwareTranslate::make()
                            ->schema(function (Get $get) {
                                /** @var array<?string> */
                                $titles = $get('title');
                                $required = collect($titles)->every(fn ($item) => $item === null || trim($item) === '');

                                return [
                                    Textarea::make('title')
                                        ->label(__('page.resource.title'))
                                        ->lazy()
                                        ->required($required),
                                    Textarea::make('content')
                                        ->label(__('page.resource.content'))
                                        ->rows(20)
                                        ->columnSpanFull(),
                                ];
                            })
                            ->columnSpanFull()
                            ->suffixLocaleLabel(),
                    ])->columnSpan([
                        'default' => 1,
                        'sm' => 4,
                    ]),
                    Group::make([
                        Section::make([
                            Radio::make('status')
                                ->default(Status::Draft)
                                ->options(Status::class)
                                ->required(),
                        ]),
                        Creator::getComponent(static::canViewAll()),
                    ])->columnSpan([
                        'default' => 1,
                        'sm' => 2,
                    ]),
                ]),
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

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
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
            ->columns([
                TranslatableTextColumn::make('title')
                    ->label(__('page.resource.title'))
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('creator.name')
                    ->label(ucfirst(__('validation.attributes.creator')))
                    ->searchable()
                    ->visible(static::canViewAll()),
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
                    ExportBulkAction::make()
                        ->exporter(PageExporter::class),
                ]),
            ]);
    }
}
