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
                Forms\Components\Select::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => Warehouse::optionsForCurrentUser())
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('product_template_id')
                    ->label('Шаблон товара')
                    ->relationship('productTemplate', 'name')
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
                Forms\Components\DatePicker::make('arrival_date')
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
                Tables\Columns\TextColumn::make('producer')
                    ->label('Производитель')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),
                Tables\Columns\TextColumn::make('available_quantity')
                    ->label('Доступное количество')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(function (string $state): string {
                        if ($state > 10) return 'success';
                        if ($state > 0) return 'warning';
                        return 'danger';
                    }),
                Tables\Columns\TextColumn::make('available_volume')
                    ->label('Доступный объем (м³)')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ' ',
                    )
                    ->sortable(),
                // Убираем колонку статуса, так как работаем с агрегированными данными
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => Warehouse::optionsForCurrentUser()),
                Tables\Filters\SelectFilter::make('producer')
                    ->label('Производитель')
                    ->options(fn () => Product::distinct()->pluck('producer', 'producer')->filter()),
                // Убираем фильтр по статусу, так как работаем с агрегированными данными
                Tables\Filters\Filter::make('in_stock')
                    ->label('В наличии')
                    ->query(fn (Builder $query): Builder => $query->having('available_quantity', '>', 0)),

            ])
            ->actions([
                // Убираем View и Edit, так как работаем с агрегированными данными
            ])
            ->bulkActions([
                // Убираем bulk actions для агрегированных данных
            ])
            ->defaultSort('available_quantity', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = Product::query();

        // Фильтрация по компании пользователя
        if ($user->company_id) {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        // Группируем товары по производителю, складу и шаблону
        $query->select([
            DB::raw('CONCAT(product_template_id, "_", warehouse_id, "_", COALESCE(producer, "unknown"), "_", COALESCE(name, "unnamed")) as id'),
            'product_template_id',
            'warehouse_id',
            'producer',
            'name',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(calculated_volume * quantity) as total_volume'),
            DB::raw('COUNT(*) as items_count'),
            DB::raw('MIN(arrival_date) as first_arrival'),
            DB::raw('MAX(arrival_date) as last_arrival'),
        ])
        ->groupBy(['product_template_id', 'warehouse_id', 'producer', 'name']);

        // Теперь вычитаем проданные товары для расчета реальных остатков
        $query->addSelect([
            DB::raw('(SUM(quantity) - COALESCE((
                SELECT SUM(s.quantity) 
                FROM sales s 
                INNER JOIN products p2 ON s.product_id = p2.id 
                WHERE p2.product_template_id = products.product_template_id 
                AND p2.warehouse_id = products.warehouse_id 
                AND p2.producer = products.producer 
                AND p2.name = products.name
                AND s.is_active = 1
            ), 0)) as available_quantity'),
            DB::raw('(SUM(calculated_volume * quantity) - COALESCE((
                SELECT SUM(s.quantity * p2.calculated_volume) 
                FROM sales s 
                INNER JOIN products p2 ON s.product_id = p2.id 
                WHERE p2.product_template_id = products.product_template_id 
                AND p2.warehouse_id = products.warehouse_id 
                AND p2.producer = products.producer 
                AND p2.name = products.name
                AND s.is_active = 1
            ), 0)) as available_volume')
        ]);

        return $query;
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
            // Убираем create, view, edit - работаем только со списком остатков
        ];
    }

    /**
     * Получить ключ записи для таблицы
     * Используем составной ключ для агрегированных данных
     */
    public static function getTableRecordKey($record): string
    {
        if (is_array($record)) {
            return $record['id'] ?? 'unknown';
        }
        
        if (is_object($record) && method_exists($record, 'getAttribute')) {
            return $record->getAttribute('id') ?? 'unknown';
        }
        
        return 'unknown';
    }
} 