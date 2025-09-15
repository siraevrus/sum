<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestResource\Pages;
use App\Models\ProductTemplate;
use App\Models\Request;
use App\UserRole;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Запросы';

    protected static ?string $modelLabel = 'Запрос';

    protected static ?string $pluralModelLabel = 'Запросы';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return in_array($user->role->value, [
            'admin',
            'warehouse_worker',
            'sales_manager',
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label('Заголовок')
                                    ->required()
                                    ->maxLength(255),

                                Select::make('warehouse_id')
                                    ->label('Склад')
                                    ->options(fn () => \App\Models\Warehouse::optionsForCurrentUser())
                                    ->default(function () {
                                        $user = Auth::user();
                                        if (! $user) {
                                            return null;
                                        }

                                        return method_exists($user, 'isAdmin') && $user->isAdmin() ? null : $user->warehouse_id;
                                    })
                                    ->required()
                                    ->searchable(),

                                Select::make('product_template_id')
                                    ->label('Шаблон товара')
                                    ->options(ProductTemplate::pluck('name', 'id'))
                                    ->searchable()
                                    ->placeholder('Выберите шаблон')
                                    ->required(),

                                TextInput::make('quantity')
                                    ->label('Количество')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),

                                Select::make('status')
                                    ->label('Статус')
                                    ->options([
                                        Request::STATUS_PENDING => 'Ожидает рассмотрения',
                                        Request::STATUS_APPROVED => 'Одобрен',
                                    ])
                                    ->default(Request::STATUS_PENDING)
                                    ->required()
                                    ->visible(fn () => (bool) (Auth::user()?->isAdmin() ?? false))
                                    ->dehydrated(fn () => (bool) (Auth::user()?->isAdmin() ?? false)),

                            ]),

                        Textarea::make('description')
                            ->label('Описание')
                            ->placeholder('Укажите характеристики и количество запрашиваемого материала')
                            ->rows(4)
                            ->maxLength(2000),

                        Textarea::make('admin_notes')
                            ->label('Заметки администратора')
                            ->rows(3)
                            ->maxLength(1000)
                            ->visible(function () {
                                return Auth::user()->role === 'admin';
                            }),
                    ]),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Сотрудник')
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => Request::STATUS_PENDING,
                        'info' => Request::STATUS_APPROVED,
                    ])
                    ->formatStateUsing(function (Request $record): string {
                        return $record->getStatusLabel();
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),

            ])
            ->emptyStateHeading('Нет запросов')
            ->emptyStateDescription('Создайте первый запрос, чтобы начать работу.')
            ->recordUrl(fn (\App\Models\Request $record) => self::getUrl('view', ['record' => $record]))
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => \App\Models\Warehouse::optionsForCurrentUser())
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        Request::STATUS_PENDING => 'Ожидает рассмотрения',
                        Request::STATUS_APPROVED => 'Одобрен',
                    ]),

            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label(''),
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->visible(function (\App\Models\Request $record): bool {
                        $user = Auth::user();
                        if (! $user) {
                            return false;
                        }

                        // Hide edit button for sales managers on approved requests
                        if ($user->role === UserRole::SALES_MANAGER && $record->status === Request::STATUS_APPROVED) {
                            return false;
                        }

                        // Hide edit button for warehouse workers on approved requests
                        if ($user->role === UserRole::WAREHOUSE_WORKER && $record->status === Request::STATUS_APPROVED) {
                            return false;
                        }

                        return true;
                    }),
                Tables\Actions\DeleteAction::make()->label(''),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListRequests::route('/'),
            'create' => Pages\CreateRequest::route('/create'),
            'view' => Pages\ViewRequest::route('/{record}'),
            'edit' => Pages\EditRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // Администратор видит все запросы
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return parent::getEloquentQuery();
        }

        // Остальные пользователи видят только свои запросы
        return parent::getEloquentQuery()
            ->where('user_id', $user?->id);
    }
}
