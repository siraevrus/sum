<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Warehouse;
use Filament\Pages\Page;
use Filament\Pages\Concerns\HasHeaderWidgets;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StockOverview extends Page implements HasTable
{
    use InteractsWithTable, HasHeaderWidgets;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Остатки';

    protected static ?string $title = 'Остатки товаров';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.stock-overview';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
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
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('calculated_volume')
                    ->label('Объем (м³)')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ' ',
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('productTemplate.name')
                    ->label('Шаблон')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(Warehouse::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('producer')
                    ->label('Производитель')
                    ->options(fn () => Product::distinct()->pluck('producer', 'producer')->filter()),
                Tables\Filters\Filter::make('in_stock')
                    ->label('В наличии')
                    ->query(fn (Builder $query): Builder => $query->where('quantity', '>', 0)),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Мало остатков')
                    ->query(fn (Builder $query): Builder => $query->where('quantity', '<=', 10)->where('quantity', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Product::query();

        // Фильтрация по компании пользователя
        if ($user->company_id) {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
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

        return $query->distinct()->pluck('producer')->filter()->toArray();
    }

    public function getWarehouses()
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
     * Сводка по характеристикам товаров: для каждого имени характеристики
     * агрегируем значения и считаем количество позиций, суммарное количество и общий объём.
     *
     * @return array<string, array<string, array{items:int,quantity:int,total_volume:float}>>
     */
    public function getAttributeSummary(int $limitPerAttribute = 10): array
    {
        $products = $this->getTableQuery()
            ->select(['attributes', 'quantity', 'calculated_volume'])
            ->get();

        $summary = [];

        foreach ($products as $product) {
            $attributes = $product->attributes ?? [];
            if (!is_array($attributes)) {
                continue;
            }

            foreach ($attributes as $attributeName => $attributeValue) {
                // Преобразуем сложные значения в строку для группировки
                if (is_array($attributeValue)) {
                    $attributeValue = json_encode($attributeValue, JSON_UNESCAPED_UNICODE);
                }

                $valueKey = (string) $attributeValue;

                if (!isset($summary[$attributeName][$valueKey])) {
                    $summary[$attributeName][$valueKey] = [
                        'items' => 0,
                        'quantity' => 0,
                        'total_volume' => 0.0,
                    ];
                }

                $summary[$attributeName][$valueKey]['items'] += 1;
                $summary[$attributeName][$valueKey]['quantity'] += (int) ($product->quantity ?? 0);
                $summary[$attributeName][$valueKey]['total_volume'] += (float) (($product->calculated_volume ?? 0) * ($product->quantity ?? 0));
            }
        }

        // Сортируем значения по суммарному количеству по убыванию и ограничиваем топ-N
        foreach ($summary as $attributeName => $values) {
            uasort($values, function ($a, $b) {
                return ($b['quantity'] <=> $a['quantity'])
                    ?: ($b['items'] <=> $a['items']);
            });
            if ($limitPerAttribute > 0) {
                $summary[$attributeName] = array_slice($values, 0, $limitPerAttribute, true);
            } else {
                $summary[$attributeName] = $values;
            }
        }

        // Сортируем список характеристик по имени
        ksort($summary, SORT_NATURAL | SORT_FLAG_CASE);

        return $summary;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\DashboardStats::class,
            \App\Filament\Widgets\PopularProducts::class,
        ];
    }
}