<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\Product;
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

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Продажи';

    protected static ?string $modelLabel = 'Продажа';

    protected static ?string $pluralModelLabel = 'Продажи';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        return in_array($user->role->value, [
            'admin',
            'operator',
            'warehouse_worker'
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
                                TextInput::make('sale_number')
                                    ->label('Номер продажи')
                                    ->default(Sale::generateSaleNumber())
                                    ->disabled()
                                    ->required(),

                                Select::make('product_id')
                                    ->label('Товар')
                                    ->options(Product::where('quantity', '>', 0)->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $productId = $get('product_id');
                                        if ($productId) {
                                            $product = Product::find($productId);
                                            if ($product) {
                                                $set('warehouse_id', $product->warehouse_id);
                                            }
                                        }
                                    }),

                                Select::make('warehouse_id')
                                    ->label('Склад')
                                    ->options(Warehouse::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->disabled(),

                                TextInput::make('quantity')
                                    ->label('Количество')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $quantity = $get('quantity');
                                        $unitPrice = $get('unit_price');
                                        if ($quantity && $unitPrice) {
                                            $set('price_without_vat', $quantity * $unitPrice);
                                        }
                                    }),

                                TextInput::make('unit_price')
                                    ->label('Цена за единицу')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $quantity = $get('quantity');
                                        $unitPrice = $get('unit_price');
                                        if ($quantity && $unitPrice) {
                                            $set('price_without_vat', $quantity * $unitPrice);
                                        }
                                    }),

                                TextInput::make('price_without_vat')
                                    ->label('Сумма без НДС')
                                    ->numeric()
                                    ->disabled()
                                    ->required(),

                                TextInput::make('total_price')
                                    ->label('Общая сумма')
                                    ->numeric()
                                    ->disabled()
                                    ->required(),

                                Select::make('payment_method')
                                    ->label('Способ оплаты')
                                    ->options([
                                        Sale::PAYMENT_METHOD_CASH => 'Нал',
                                        Sale::PAYMENT_METHOD_NOCASH => 'Безнал',
                                        Sale::PAYMENT_METHOD_NOCASH_AND_CASH => 'Нал + безнал',
                                    ])
                                    ->default(Sale::PAYMENT_METHOD_CASH)
                                    ->required(),

                                Select::make('payment_status')
                                    ->label('Статус оплаты')
                                    ->options([
                                        Sale::PAYMENT_STATUS_PENDING => 'Ожидает оплаты',
                                        Sale::PAYMENT_STATUS_PAID => 'Оплачено',
                                        Sale::PAYMENT_STATUS_PARTIALLY_PAID => 'Частично оплачено',
                                        Sale::PAYMENT_STATUS_CANCELLED => 'Отменено',
                                    ])
                                    ->default(Sale::PAYMENT_STATUS_PENDING)
                                    ->required(),

                                DatePicker::make('sale_date')
                                    ->label('Дата продажи')
                                    ->required()
                                    ->default(now()),

                                DatePicker::make('delivery_date')
                                    ->label('Дата доставки'),

                                Toggle::make('is_active')
                                    ->label('Активна')
                                    ->default(true),
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
                                    ->label('Email клиента')
                                    ->email()
                                    ->maxLength(255),

                                TextInput::make('invoice_number')
                                    ->label('Номер счета')
                                    ->maxLength(255),
                            ]),

                        Textarea::make('customer_address')
                            ->label('Адрес клиента')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),

                Section::make('Дополнительная информация')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Заметки')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sale_number')
                    ->label('Номер продажи')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Товар')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Клиент')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Не указан'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Цена за ед.')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Общая сумма')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Оплата')
                    ->colors([
                        'warning' => Sale::PAYMENT_STATUS_PENDING,
                        'success' => Sale::PAYMENT_STATUS_PAID,
                        'info' => Sale::PAYMENT_STATUS_PARTIALLY_PAID,
                        'danger' => Sale::PAYMENT_STATUS_CANCELLED,
                    ])
                    ->formatStateUsing(function (Sale $record): string {
                        return $record->getPaymentStatusLabel();
                    })
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('delivery_status')
                    ->label('Доставка')
                    ->colors([
                        'warning' => Sale::DELIVERY_STATUS_PENDING,
                        'info' => Sale::DELIVERY_STATUS_IN_PROGRESS,
                        'success' => Sale::DELIVERY_STATUS_DELIVERED,
                        'danger' => Sale::DELIVERY_STATUS_CANCELLED,
                    ])
                    ->formatStateUsing(function (Sale $record): string {
                        return $record->getDeliveryStatusLabel();
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Дата продажи')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Продавец')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(Warehouse::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('payment_status')
                    ->label('Статус оплаты')
                    ->options([
                        Sale::PAYMENT_STATUS_PENDING => 'Ожидает оплаты',
                        Sale::PAYMENT_STATUS_PAID => 'Оплачено',
                        Sale::PAYMENT_STATUS_PARTIALLY_PAID => 'Частично оплачено',
                        Sale::PAYMENT_STATUS_CANCELLED => 'Отменено',
                    ]),

                SelectFilter::make('delivery_status')
                    ->label('Статус доставки')
                    ->options([
                        Sale::DELIVERY_STATUS_PENDING => 'Ожидает доставки',
                        Sale::DELIVERY_STATUS_IN_PROGRESS => 'В доставке',
                        Sale::DELIVERY_STATUS_DELIVERED => 'Доставлено',
                        Sale::DELIVERY_STATUS_CANCELLED => 'Отменено',
                    ]),

                SelectFilter::make('payment_method')
                    ->label('Способ оплаты')
                    ->options([
                        Sale::PAYMENT_METHOD_CASH => 'Наличные',
                        Sale::PAYMENT_METHOD_CARD => 'Карта',
                        Sale::PAYMENT_METHOD_BANK_TRANSFER => 'Банковский перевод',
                        Sale::PAYMENT_METHOD_OTHER => 'Другое',
                    ]),

                Filter::make('delivery_overdue')
                    ->label('Просроченные доставки')
                    ->query(function (Builder $query): Builder {
                        return $query->where('delivery_status', Sale::DELIVERY_STATUS_IN_PROGRESS)
                                   ->where('sale_date', '<', now()->subDays(7));
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
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\Action::make('process_sale')
                    ->label('Оформить продажу')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(function (Sale $record): bool {
                        return $record->canBeSold() && $record->payment_status === Sale::PAYMENT_STATUS_PENDING;
                    })
                    ->action(function (Sale $record): void {
                        $record->processSale();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Оформить продажу')
                    ->modalDescription('Товар будет списан со склада и продажа будет помечена как оплаченная.')
                    ->modalSubmitActionLabel('Оформить'),

                Tables\Actions\Action::make('cancel_sale')
                    ->label('Отменить продажу')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(function (Sale $record): bool {
                        return $record->payment_status !== Sale::PAYMENT_STATUS_CANCELLED;
                    })
                    ->action(function (Sale $record): void {
                        $record->cancelSale();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Отменить продажу')
                    ->modalDescription('Товар будет возвращен на склад и продажа будет отменена.')
                    ->modalSubmitActionLabel('Отменить'),

                Tables\Actions\DeleteAction::make()->label(''),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Экспорт')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(route('sales.export'))
                    ->openUrlInNewTab(),
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
        
        // Администратор видит все продажи
        if ($user->role === 'admin') {
            return parent::getEloquentQuery();
        }
        
        // Остальные пользователи видят только продажи на своих складах
        return parent::getEloquentQuery()
            ->whereHas('warehouse', function (Builder $query) use ($user) {
                $query->where('company_id', $user->company_id);
            });
    }
} 