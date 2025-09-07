<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Реализация';

    protected static ?string $modelLabel = 'Реализация';

    protected static ?string $pluralModelLabel = 'Реализация';

    protected static ?int $navigationSort = 6;

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

    /**
     * Рассчитать общую сумму продажи
     */
    private static function calculateTotalPrice(Set $set, Get $get): void
    {
        $cashAmount = (float) ($get('cash_amount') ?? 0);
        $nocashAmount = (float) ($get('nocash_amount') ?? 0);
        $totalPrice = $cashAmount + $nocashAmount;

        $set('total_price', $totalPrice);
    }

    /**
     * Получить максимальное доступное количество товара
     */
    private static function getMaxAvailableQuantity(string $productId): int
    {
        // Получаем товар по ID
        $product = \App\Models\Product::find($productId);
        if (! $product) {
            return 0;
        }

        // Доступное количество = сумма количеств минус сумма проданных по products с теми же характеристиками
        $availableQuantity = \App\Models\Product::where('product_template_id', $product->product_template_id)
            ->where('warehouse_id', $product->warehouse_id)
            ->where('producer_id', $product->producer_id) // Используем producer_id
            ->where('status', \App\Models\Product::STATUS_IN_STOCK)
            ->where('is_active', true)
            ->selectRaw('SUM(quantity - COALESCE(sold_quantity, 0)) as available_quantity')
            ->value('available_quantity');

        return max(0, $availableQuantity ?? 0);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('sale_number')
                                    ->label('Номер продажи')
                                    ->default(Sale::generateSaleNumber())
                                    ->disabled()
                                    ->required(),

                                Select::make('warehouse_id')
                                    ->label('Склад')
                                    ->options(fn () => Warehouse::optionsForCurrentUser())
                                    ->required()
                                    ->live()
                                    ->debounce(300)
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        // Сбрасываем выбор товара при смене склада
                                        $set('product_id', null);
                                        $set('quantity', 1);
                                        // Обновляем общую сумму
                                        static::calculateTotalPrice($set, $get);
                                    }),

                                DatePicker::make('sale_date')
                                    ->label('Дата продажи')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\Select::make('product_id')
                                    ->label('Товар')
                                    ->options(function (Get $get) {
                                        $record = $get('record');
                                        if ($record && $record->exists) {
                                            return [];
                                        }
                                        $warehouseId = $get('warehouse_id');
                                        if (! $warehouseId) {
                                            return [];
                                        }

                                        // Получаем доступные товары с группировкой
                                        $availableProducts = Product::query()
                                            ->select([
                                                'product_template_id',
                                                'warehouse_id',
                                                'producer_id', // Используем producer_id
                                                'name',
                                                DB::raw('SUM(quantity - COALESCE(sold_quantity, 0)) as available_quantity'),
                                            ])
                                            ->where('warehouse_id', $warehouseId)
                                            ->where('status', Product::STATUS_IN_STOCK)
                                            ->where('is_active', true)
                                            ->groupBy(['product_template_id', 'warehouse_id', 'producer_id', 'name']) // Группируем по producer_id
                                            ->having('available_quantity', '>', 0)
                                            ->get();

                                        $options = [];
                                        foreach ($availableProducts as $product) {
                                            $producerLabel = '';
                                            if ($product->producer_id) {
                                                $producer = \App\Models\Producer::find($product->producer_id);
                                                if ($producer) {
                                                    $producerLabel = " ({$producer->name})";
                                                }
                                            }

                                            $displayName = "{$product->name}{$producerLabel} - Доступно: {$product->available_quantity}";

                                            // Используем составной ключ для уникальности
                                            $compositeKey = "{$product->product_template_id}|{$product->warehouse_id}|{$product->producer_id}|{$product->name}";
                                            $options[$compositeKey] = $displayName;
                                        }

                                        return $options;
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->debounce(300)
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $set('quantity', 1);
                                        static::calculateTotalPrice($set, $get);
                                    })
                                    ->visible(fn ($get) => ! ($get('record') && $get('record')->exists)),

                                Forms\Components\Placeholder::make('product_info')
                                    ->label('Товар')
                                    ->content(function ($get) {
                                        $record = $get('record');
                                        if ($record && $record->exists) {
                                            $product = Product::find($record->product_id);

                                            return $product ? "{$product->id} — {$product->name}" : '—';
                                        }

                                        return null;
                                    })
                                    ->visible(fn ($get) => $get('record') && $get('record')->exists),

                                TextInput::make('quantity')
                                    ->label('Количество')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live()
                                    ->debounce(300)
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        static::calculateTotalPrice($set, $get);
                                    })
                                    ->maxValue(function (Get $get) {
                                        $productId = $get('product_id');
                                        if ($productId) {
                                            return static::getMaxAvailableQuantity($productId);
                                        }

                                        return 999999;
                                    }),

                                TextInput::make('total_price')
                                    ->label('Общая сумма')
                                    ->numeric()
                                    ->disabled()
                                    ->required(),

                                Toggle::make('is_active')
                                    ->label('Активна')
                                    ->hidden()
                                    ->default(true),
                            ]),

                        // Компактная сетка для финансовых полей
                        Grid::make(4)
                            ->schema([
                                TextInput::make('cash_amount')
                                    ->label('Сумма (нал)')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->debounce(300)
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        static::calculateTotalPrice($set, $get);
                                    }),

                                TextInput::make('nocash_amount')
                                    ->label('Сумма (безнал)')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->debounce(300)
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        static::calculateTotalPrice($set, $get);
                                    }),

                                Select::make('currency')
                                    ->label('Валюта')
                                    ->options([
                                        'RUB' => 'Руб',
                                        'USD' => 'USD',
                                        'UZS' => 'Сумы',
                                    ])
                                    ->default('RUB')
                                    ->required(),

                                TextInput::make('exchange_rate')
                                    ->label('Курс валюты')
                                    ->default(1)
                                    ->helperText('Курс валюты к рублю'),
                            ]),
                    ]),

                Section::make('Информация о клиенте')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('customer_name')
                                    ->label('Имя клиента')
                                    ->maxLength(255),

                                TextInput::make('customer_phone')
                                    ->label('Телефон клиента')
                                    ->tel()
                                    ->maxLength(255),

                                TextInput::make('customer_email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255),

                                TextInput::make('customer_address')
                                    ->label('Адрес')
                                    ->maxLength(500)
                                    ->columnSpan(1),
                            ]),
                    ]),

                Section::make('Дополнительная информация')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Заметки')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Дата продажи')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_number')
                    ->label('Номер продажи')
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Товар')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Клиент')
                    ->sortable()
                    ->placeholder('Не указан'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Общая сумма')
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 2, '.', ' '))
                    ->suffix(fn ($record) => ' '.($record->currency ?? ''))
                    ->sortable(),

                // Tables\Columns\TextColumn::make('exchange_rate')
                //     ->label('Курс валюты')
                //     ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, '.', ' ') : '1.00')
                //     ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Продавец')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Статус оплаты')
                    ->formatStateUsing(fn ($state, $record) => $record->getPaymentStatusLabel())
                    ->badge()
                    ->color(fn ($record) => $record->getPaymentStatusColor())
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => Warehouse::optionsForCurrentUser()),

                SelectFilter::make('payment_status')
                    ->label('Статус оплаты')
                    ->options([
                        \App\Models\Sale::PAYMENT_STATUS_PAID => 'Оплачено',
                        \App\Models\Sale::PAYMENT_STATUS_CANCELLED => 'Отменено',
                    ]),

                SelectFilter::make('user_id')
                    ->label('Продавец')
                    ->options(function () {
                        return \App\Models\User::pluck('name', 'id')->toArray();
                    }),

                Filter::make('active')
                    ->label('Только активные')
                    ->query(function (Builder $query): Builder {
                        return $query->where('is_active', true);
                    }),

                Filter::make('date_range')
                    ->label('Период продаж')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('С даты'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('По дату'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->where('sale_date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->where('sale_date', '<=', $date),
                            );
                    }),
            ])
            ->emptyStateHeading('Нет продаж')
            ->emptyStateDescription('Создайте первую продажу, чтобы начать работу.')
            ->actions([
                Tables\Actions\ViewAction::make()->label(''),
                // Tables\Actions\EditAction::make()
                //    ->label('')
                //    ->visible(function (Sale $record) {
                //        return $record->payment_status !== Sale::PAYMENT_STATUS_CANCELLED;
                //    }),
                // Списывание теперь происходит автоматически при создании, кнопка не нужна

                Tables\Actions\Action::make('cancel_sale')
                    ->label('Отменить продажу')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(function (Sale $record): bool {
                        return $record->payment_status !== Sale::PAYMENT_STATUS_CANCELLED;
                    })
                    ->form([
                        Forms\Components\Textarea::make('reason_cancellation')
                            ->label('Причины отмены')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Sale $record, array $data): void {
                        $record->cancelSale($data['reason_cancellation']);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Отменить продажу')
                    ->modalDescription('Товар будет возвращен на склад и продажа будет отменена.')
                    ->modalSubmitActionLabel('ОК'),
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
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'view' => Pages\ViewSale::route('/{record}'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        if ($user->role->value === 'admin') {
            return parent::getEloquentQuery();
        }

        // Не админ — только свой склад
        if ($user->warehouse_id) {
            return parent::getEloquentQuery()->where('warehouse_id', $user->warehouse_id);
        }

        return parent::getEloquentQuery()->whereRaw('1 = 0');
    }
}
