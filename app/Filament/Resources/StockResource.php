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
            ->recordCheckboxPosition(null)
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
                    ->label('Доступно')
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

                Tables\Columns\TextColumn::make('total_sold_quantity')
                    ->label('Продано')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('danger')
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Итого')
                    ),

                Tables\Columns\TextColumn::make('total_volume')
                    ->label('Объем (м³)')
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

        // Получаем переменные характеристик для группировки (только number и select типы)
        $groupingVariables = \App\Models\ProductAttribute::query()
            ->whereIn('type', ['number', 'select'])
            ->distinct()
            ->pluck('variable')
            ->toArray();

        // Создаем GROUP BY условия для группировки по характеристикам
        $groupByAttributes = [];
        $jsonExtracts = [];

        foreach ($groupingVariables as $variable) {
            $groupByAttributes[] = DB::raw("JSON_EXTRACT(attributes, \"$.{$variable}\")");
            $jsonExtracts[] = "COALESCE(JSON_EXTRACT(attributes, \"$.{$variable}\"), \"\")";
        }

        // Создаем уникальный ID для группированной записи
        $uniqueIdSQL = ! empty($jsonExtracts)
            ? 'CONCAT(product_template_id, "_", warehouse_id, "_", producer_id, "_", HEX(SUBSTR(QUOTE(CONCAT('.implode(', ', $jsonExtracts).')), 2, 8)))'
            : 'CONCAT(product_template_id, "_", warehouse_id, "_", producer_id)';

        // Возвращаем запрос с группировкой
        return $baseQuery
            ->select([
                DB::raw("{$uniqueIdSQL} as id"),
                DB::raw('MIN(name) as name'),
                'product_template_id',
                'warehouse_id',
                'producer_id',
                DB::raw('MIN(attributes) as attributes'),
                DB::raw('SUM(quantity - COALESCE(sold_quantity, 0)) as total_quantity'),
                DB::raw('SUM(COALESCE(sold_quantity, 0)) as total_sold_quantity'),
                DB::raw('SUM((quantity - COALESCE(sold_quantity, 0)) * calculated_volume / quantity) as total_volume'),
                DB::raw('COUNT(*) as product_count'),
                DB::raw('MAX(arrival_date) as last_arrival_date'),
                DB::raw('MIN(created_at) as first_created_at'),
                DB::raw('MAX(created_at) as last_created_at'),
            ])
            ->with(['producer', 'productTemplate', 'warehouse'])
            ->groupBy(array_merge([
                'product_template_id',
                'warehouse_id',
                'producer_id',
            ], $groupByAttributes))
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
        // Используем сгенерированный ID из запроса
        if (is_object($record) && isset($record->id)) {
            return (string) $record->id;
        }

        if (is_array($record) && isset($record['id'])) {
            return (string) $record['id'];
        }

        // Fallback - всегда возвращаем строку
        return md5(serialize($record) ?: 'empty_record');
    }
}
