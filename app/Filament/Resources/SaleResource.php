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

    protected static ?string $navigationGroup = 'Реализация';

    protected static ?string $modelLabel = 'Реализация';

    protected static ?string $pluralModelLabel = 'Реализация';

    protected static ?int $navigationSort = 6;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
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
                                TextInput::make('sale_number')
                                    ->label('Номер продажи')
                                    ->default(Sale::generateSaleNumber())
                                    ->disabled()
                                    ->required(),

                                Select::make('warehouse_id')
                                    ->label('Склад')
                                    ->options(function () {
                                        $user = Auth::user();
                                        if ($user->role->value === 'warehouse_worker') {
                                            // Работник склада видит только свой склад
                                            return Warehouse::where('id', $user->warehouse_id)->pluck('name', 'id');
                                        }
                                        // Админ и менеджер видят все склады
                                        return Warehouse::pluck('name', 'id');
                                    })
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        // Очищаем товар при смене склада
                                        $set('product_id', null);
                                    }),

                                Select::make('product_id')
                                    ->label('Товар')
                                    ->options(function (Get $get) {
                                        $warehouseId = $get('warehouse_id');
                                        if (!$warehouseId) {
                                            return [];
                                        }
                                        return Product::where('warehouse_id', $warehouseId)
                                            ->where('quantity', '>', 0)
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        // Очищаем количество при смене товара
                                        $set('quantity', 1);
                                    }),

                                TextInput::make('quantity')
                                    ->label('Количество')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $productId = $get('product_id');
                                        $quantity = $get('quantity');
                                        if ($productId && $quantity) {
                                            $product = Product::find($productId);
                                            if ($product && $product->quantity < (int)$quantity) {
                                                $set('quantity', $product->quantity);
                                            }
                                        }
                                    })
                                    ->dehydrateStateUsing(function ($state, Get $get) {
                                        $productId = $get('product_id');
                                        if ($productId && $state) {
                                            $product = Product::find($productId);
                                            if ($product && $product->quantity < (int)$state) {
                                                throw new \Exception("Недостаточно товара на складе. Доступно: {$product->quantity}");
                                            }
                                        }
                                        return $state;
                                    }),



                                TextInput::make('cash_amount')
                                    ->label('Сумма (нал)')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live(500)
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $cashAmount = $get('cash_amount') ?? 0;
                                        $nocashAmount = $get('nocash_amount') ?? 0;
                                        $set('total_price', (float)$cashAmount + (float)$nocashAmount);
                                    }),

                                TextInput::make('nocash_amount')
                                    ->label('Сумма (безнал)')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live(500)
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $cashAmount = $get('cash_amount') ?? 0;
                                        $nocashAmount = $get('nocash_amount') ?? 0;
                                        $set('total_price', (float)$cashAmount + (float)$nocashAmount);
                                    }),

                                TextInput::make('total_price')
                                    ->label('Общая сумма')
                                    ->numeric()
                                    ->disabled()
                                    ->required(),

                                DatePicker::make('sale_date')
                                    ->label('Дата продажи')
                                    ->required()
                                    ->default(now()),

                                Toggle::make('is_active')
                                    ->label('Активна')
                                    ->hidden()
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

                            ]),
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

                Tables\Columns\TextColumn::make('cash_amount')
                    ->label('Сумма (нал)')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nocash_amount')
                    ->label('Сумма (безнал)')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Дата продажи')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Продавец')
                    ->sortable(),

            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(Warehouse::pluck('name', 'id')),

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
        if ($user->role->value === 'admin') {
            return parent::getEloquentQuery();
        }
        
        // Остальные пользователи видят только продажи на своих складах
        return parent::getEloquentQuery()
            ->whereHas('warehouse', function (Builder $query) use ($user) {
                if ($user->company_id) {
                    $query->where('company_id', $user->company_id);
                }
            });
    }
} 