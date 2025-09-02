<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Models\Product;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Остатки';

    protected static ?string $modelLabel = 'Остаток';

    protected static ?string $pluralModelLabel = 'Остатки';

    protected static ?int $navigationSort = 7;

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
                Forms\Components\Select::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => Warehouse::optionsForCurrentUser())
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('name')
                    ->label('Наименование')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Количество')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('producer')
                    ->label('Производитель')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('actual_arrival_date')
                    ->label('Дата поступления')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Наименование')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('producer.name')
                    ->label('Производитель')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Доступное количество')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(function (string $state): string {
                        if ($state > 10) {
                            return 'success';
                        }
                        if ($state > 0) {
                            return 'warning';
                        }

                        return 'danger';
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Итого')
                    ),

                Tables\Columns\TextColumn::make('total_volume')
                    ->label('Доступный объем (м³)')
                    ->formatStateUsing(function ($state) {
                        return $state ? number_format($state, 3, '.', ' ') : '0.000';
                    })
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Итого (м³)')
                            ->formatStateUsing(function ($state) {
                                return number_format($state, 3, '.', ' ');
                            })
                    ),

                Tables\Columns\TextColumn::make('product_count')
                    ->label('Кол-во позиций')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('last_arrival_date')
                    ->label('Последнее поступление')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => Warehouse::optionsForCurrentUser()),
                Tables\Filters\SelectFilter::make('producer_id')
                    ->label('Производитель')
                    ->options(fn () => \App\Models\Producer::pluck('name', 'id')),
                // Убраны фильтры 'in_stock' и 'low_stock'
            ])
            ->actions([
                // Убрано действие просмотра
            ])
            ->bulkActions([
                // Нет пакетных действий
            ])
            ->emptyStateHeading('Нет товаров на складе')
            ->emptyStateDescription('Товары появятся здесь после поступления на склад. Товары с одинаковыми характеристиками автоматически группируются.')
            ->defaultSort('total_quantity', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return Product::query()->whereRaw('1 = 0');
        }

        $baseQuery = Product::query()
            ->where('status', Product::STATUS_IN_STOCK)
            ->where('is_active', true);

        if ($user->role->value !== 'admin' && $user->warehouse_id) {
            $baseQuery->where('warehouse_id', $user->warehouse_id);
        }

        // Используем простой подход без сложных GROUP BY для совместимости
        return $baseQuery
            ->select([
                'id',
                'product_template_id',
                'warehouse_id',
                'producer_id',
                'name',
                'description',
                'arrival_date',
                'quantity',
                'sold_quantity',
                'calculated_volume',
                'is_active',
                'status',
                // Добавляем вычисляемые столбцы для совместимости с таблицей
                DB::raw('(quantity - COALESCE(sold_quantity, 0)) as total_quantity'),
                DB::raw('(calculated_volume * quantity) as total_volume'),
                DB::raw('1 as product_count'),
                DB::raw('arrival_date as last_arrival_date'),
            ])
            ->with('producer')
            ->orderBy('name')
            ->orderBy('producer_id');
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
            'index' => Pages\ListStocks::route('/'),
        ];
    }

    /**
     * Получить ключ записи для таблицы
     */
    public static function getTableRecordKey($record): string
    {
        if (is_object($record) && isset($record->id)) {
            return (string) $record->id;
        }

        if (is_array($record) && isset($record['id'])) {
            return (string) $record['id'];
        }

        // Fallback - создаем ключ из характеристик
        if (is_object($record)) {
            $templateId = $record->product_template_id ?? '';
            $warehouseId = $record->warehouse_id ?? '';
            $producerId = $record->producer_id ?? '';
            $attributes = is_array($record->attributes) ? json_encode($record->attributes) : '';

            return md5($templateId.'_'.$warehouseId.'_'.$producerId.'_'.$attributes);
        }

        return md5(serialize($record));
    }
}
