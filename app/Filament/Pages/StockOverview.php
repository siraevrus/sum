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
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->numeric()
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Итого')
                    ),
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
                    ->query(fn (Builder $query): Builder => $query->where('quantity', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        if (!$user) {
            return Product::query()->whereRaw('1 = 0');
        }
        
        $query = Product::query()->with('producer');

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
            $query->where('quantity', '>', 0);
        }

        return $query;
    }

    public function getProducers(): array
    {
        $user = Auth::user();
        $query = Product::query();

        // Фильтрация по компании пользователя
        if ($user->company_id) {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        // Группируем по производителю через связь
        return $query->whereHas('producer')
            ->with('producer')
            ->get()
            ->groupBy('producer.id')
            ->map(function ($products) {
                $producer = $products->first()->producer;
                return $producer ? $producer->name : null;
            })
            ->filter() // Убираем null значения
            ->toArray();
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
        $producers = \App\Models\Producer::with('products')->get();

        $result = [];
        foreach ($producers as $producer) {
            $result[$producer->name] = [
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