<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestResource\Pages;
use App\Models\Request;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Запросы';

    protected static ?string $modelLabel = 'Запрос';

    protected static ?string $pluralModelLabel = 'Запросы';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        return in_array($user->role->value, [
            'admin',
            'warehouse_worker',
            'sales_manager'
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
                                    ->options(Warehouse::pluck('name', 'id'))
                                    ->required()
                                    ->searchable(),

                                Select::make('product_template_id')
                                    ->label('Шаблон товара')
                                    ->options(ProductTemplate::pluck('name', 'id'))
                                    ->searchable()
                                    ->placeholder('Выберите шаблон (необязательно)'),

                                TextInput::make('quantity')
                                    ->label('Количество')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),

                                Select::make('priority')
                                    ->label('Приоритет')
                                    ->options([
                                        Request::PRIORITY_LOW => 'Низкий',
                                        Request::PRIORITY_NORMAL => 'Обычный',
                                        Request::PRIORITY_HIGH => 'Высокий',
                                        Request::PRIORITY_URGENT => 'Срочный',
                                    ])
                                    ->default(Request::PRIORITY_NORMAL)
                                    ->required(),

                                Select::make('status')
                                    ->label('Статус')
                                    ->options([
                                        Request::STATUS_PENDING => 'Ожидает рассмотрения',
                                        Request::STATUS_APPROVED => 'Одобрен',
                                        Request::STATUS_REJECTED => 'Отклонен',
                                        Request::STATUS_IN_PROGRESS => 'В обработке',
                                        Request::STATUS_COMPLETED => 'Завершен',
                                        Request::STATUS_CANCELLED => 'Отменен',
                                    ])
                                    ->default(Request::STATUS_PENDING)
                                    ->required(),

                                Toggle::make('is_active')
                                    ->label('Активен')
                                    ->default(true),
                            ]),

                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(4)
                            ->required()
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
                    ->label('Создатель')
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),

                Tables\Columns\TextColumn::make('productTemplate.name')
                    ->label('Шаблон товара')
                    ->sortable()
                    ->placeholder('Не указан'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->sortable()
                    ->badge(),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Приоритет')
                    ->colors([
                        'gray' => Request::PRIORITY_LOW,
                        'info' => Request::PRIORITY_NORMAL,
                        'warning' => Request::PRIORITY_HIGH,
                        'danger' => Request::PRIORITY_URGENT,
                    ])
                    ->formatStateUsing(function (Request $record): string {
                        return $record->getPriorityLabel();
                    })
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => Request::STATUS_PENDING,
                        'info' => Request::STATUS_APPROVED,
                        'danger' => Request::STATUS_REJECTED,
                        'primary' => Request::STATUS_IN_PROGRESS,
                        'success' => Request::STATUS_COMPLETED,
                        'gray' => Request::STATUS_CANCELLED,
                    ])
                    ->formatStateUsing(function (Request $record): string {
                        return $record->getStatusLabel();
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Одобрен')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Обработан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Завершен')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateHeading('Нет запросов')
            ->emptyStateDescription('Создайте первый запрос, чтобы начать работу.')
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(Warehouse::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('product_template_id')
                    ->label('Шаблон')
                    ->options(ProductTemplate::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('priority')
                    ->label('Приоритет')
                    ->options([
                        Request::PRIORITY_LOW => 'Низкий',
                        Request::PRIORITY_NORMAL => 'Обычный',
                        Request::PRIORITY_HIGH => 'Высокий',
                        Request::PRIORITY_URGENT => 'Срочный',
                    ]),

                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        Request::STATUS_PENDING => 'Ожидает рассмотрения',
                        Request::STATUS_APPROVED => 'Одобрен',
                        Request::STATUS_REJECTED => 'Отклонен',
                        Request::STATUS_IN_PROGRESS => 'В обработке',
                        Request::STATUS_COMPLETED => 'Завершен',
                        Request::STATUS_CANCELLED => 'Отменен',
                    ]),

                Filter::make('overdue')
                    ->label('Просроченные')
                    ->query(function (Builder $query): Builder {
                        return $query->where('status', Request::STATUS_IN_PROGRESS)
                                   ->where('processed_at', '<', now()->subDays(7));
                    }),

                Filter::make('urgent')
                    ->label('Срочные')
                    ->query(function (Builder $query): Builder {
                        return $query->where('priority', Request::PRIORITY_URGENT);
                    }),

                Filter::make('active')
                    ->label('Только активные')
                    ->query(function (Builder $query): Builder {
                        return $query->where('is_active', true);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label(''),
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\Action::make('approve')
                    ->label('Одобрить')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(function (Request $record): bool {
                        return $record->canBeApproved() && Auth::user()->role === 'admin';
                    })
                    ->action(function (Request $record): void {
                        $record->approve();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Одобрить запрос')
                    ->modalDescription('Запрос будет одобрен и переведен в статус "Одобрен".')
                    ->modalSubmitActionLabel('Одобрить'),

                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(function (Request $record): bool {
                        return $record->canBeRejected() && Auth::user()->role === 'admin';
                    })
                    ->action(function (Request $record): void {
                        $record->reject();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Отклонить запрос')
                    ->modalDescription('Запрос будет отклонен.')
                    ->modalSubmitActionLabel('Отклонить'),

                Tables\Actions\Action::make('start_processing')
                    ->label('Начать обработку')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->visible(function (Request $record): bool {
                        return $record->canBeProcessed();
                    })
                    ->action(function (Request $record): void {
                        $record->startProcessing();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Начать обработку')
                    ->modalDescription('Запрос будет переведен в статус "В обработке".')
                    ->modalSubmitActionLabel('Начать'),

                Tables\Actions\Action::make('complete')
                    ->label('Завершить')
                    ->icon('heroicon-o-flag')
                    ->color('success')
                    ->visible(function (Request $record): bool {
                        return $record->canBeCompleted();
                    })
                    ->action(function (Request $record): void {
                        $record->complete();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Завершить запрос')
                    ->modalDescription('Запрос будет завершен.')
                    ->modalSubmitActionLabel('Завершить'),

                Tables\Actions\Action::make('cancel')
                    ->label('Отменить')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(function (Request $record): bool {
                        return $record->canBeCancelled();
                    })
                    ->action(function (Request $record): void {
                        $record->cancel();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Отменить запрос')
                    ->modalDescription('Запрос будет отменен.')
                    ->modalSubmitActionLabel('Отменить'),

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
        if ($user->role === 'admin') {
            return parent::getEloquentQuery();
        }
        
        // Остальные пользователи видят только свои запросы
        return parent::getEloquentQuery()
            ->where('user_id', $user->id);
    }
} 