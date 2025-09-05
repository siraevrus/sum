<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Warehouse;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StockOverview extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Остатки';

    protected static ?string $title = 'Остатки товаров';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.stock-overview';

    public static function canAccess(): bool
    {
        // Доступ открыт всем не заблокированным пользователям, чтобы был стартовый раздел
        $user = Auth::user();
        return $user && !$user->isBlocked();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
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
                    ->label('Количество')
                    ->numeric()
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Итого')
                    ),
                Tables\Columns\TextColumn::make('product_count')
                    ->label('Записей')
                    ->numeric()
                    ->sortable()
                    ->tooltip('Количество отдельных записей товара'),
                Tables\Columns\TextColumn::make('calculated_volume')
                    ->label('Объем (м³)')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ' ',
                    )
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Итого (м³)')
                            ->numeric(
                                decimalPlaces: 2,
                                decimalSeparator: '.',
                                thousandsSeparator: ' ',
                            )
                    ),
                Tables\Columns\TextColumn::make('productTemplate.name')
                    ->label('Шаблон')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => Warehouse::optionsForCurrentUser()),
                Tables\Filters\SelectFilter::make('producer_id')
                    ->label('Производитель (по id)')
                    ->options(fn () => \App\Models\Producer::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('producers')
                    ->label('Производитель (по имени)')
                    ->options(fn () => \App\Models\Producer::pluck('name', 'name')),
                Tables\Filters\Filter::make('in_stock')
                    ->label('В наличии')
                    ->query(fn (Builder $query): Builder => $query->having('total_quantity', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('first_created_at', 'desc');
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        if (!$user) {
            return Product::query()->whereRaw('1 = 0');
        }
        
        // Группируем товары по одинаковым характеристикам
        $query = Product::query()
            ->select([
                'name',
                'product_template_id',
                'producer_id',
                'warehouse_id',
                'attributes',
                'calculated_volume',
                \DB::raw('SUM(quantity) as total_quantity'),
                \DB::raw('COUNT(*) as product_count'),
                \DB::raw('MIN(created_at) as first_created_at'),
                \DB::raw('MAX(created_at) as last_created_at')
            ])
            ->with(['producer', 'productTemplate', 'warehouse'])
            ->where('status', 'in_stock')
            ->where('is_active', true)
            ->groupBy([
                'name',
                'product_template_id', 
                'producer_id',
                'warehouse_id',
                'attributes',
                'calculated_volume'
            ]);

        // Фильтрация по компании пользователя
        if ($user->company_id) {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        // Применяем фильтр по производителю, если он установлен
        $producerId = request()->get('tableFilters.producer_id.value');
        if ($producerId) {
            $query->where('producer_id', $producerId);
        }

        // Применяем фильтр по имени производителя, если он установлен
        $producerName = request()->get('tableFilters.producers.value');
        if ($producerName) {
            $query->whereHas('producer', function ($q) use ($producerName) {
                $q->where('name', $producerName);
            });
        }

        // Применяем фильтр по складу, если он установлен
        $warehouseId = request()->get('tableFilters.warehouse_id.value');
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        // Применяем фильтр "В наличии", если он активен
        $inStockFilter = request()->get('tableFilters.in_stock.isActive');
        if ($inStockFilter === 'true') {
            $query->having('total_quantity', '>', 0);
        }

        return $query;
    }

    public function getProducers(): array
    {
        $user = Auth::user();
        $query = \App\Models\Producer::query();

        // Фильтрация по компании пользователя, если она есть
        if ($user->company_id) {
            // Получаем ID складов, связанных с компанией пользователя
            $warehouseIds = \App\Models\Warehouse::where('company_id', $user->company_id)->pluck('id');

            // Получаем ID производителей, у которых есть товары на этих складах
            $producerIds = Product::whereIn('warehouse_id', $warehouseIds)->pluck('producer_id')->unique();

            $query->whereIn('id', $producerIds);
        }

        return $query->pluck('name', 'id')->toArray();
    }

    public function getWarehouses(): \Illuminate\Database\Eloquent\Collection
    {
        $user = Auth::user();
        $query = Warehouse::query();

        // Фильтрация по компании пользователя
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        return $query->get();
    }

    /**
     * Получить агрегированные данные по производителям
     */
    public function getProducerStats(): array
    {
        $producers = \App\Models\Producer::with(['products' => function ($query) {
            $query->where('status', 'in_stock');
        }])->get();

        $result = [];
        foreach ($producers as $producer) {
            $result[$producer->id] = [
                'name' => $producer->name,
                'total_products' => $producer->products->count(),
                'total_quantity' => $producer->products->sum('quantity'),
                'total_volume' => $producer->products->sum(function ($product) {
                    return ($product->calculated_volume ?? 0) * $product->quantity;
                }),
            ];
        }
        return $result;
    }

    /**
     * Получить агрегированные данные по складам
     */
    public function getWarehouseStats(): array
    {
        $user = Auth::user();
        $query = Product::query();

        // Фильтрация по компании пользователя
        if ($user->company_id) {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        // Группируем по складу, не учитывая наименование и характеристики
        return $query->select('warehouse_id')
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(calculated_volume * quantity) as total_volume')
            ->groupBy('warehouse_id')
            ->get()
            ->keyBy('warehouse_id')
            ->toArray();
    }
}