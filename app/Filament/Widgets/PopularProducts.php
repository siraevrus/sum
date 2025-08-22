<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Sale;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class PopularProducts extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Популярные товары';

    public function getHeading(): string
    {
        return 'Популярные товары';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->withCount(['sales as total_sales' => function (Builder $query) {
                        $query->where('payment_status', Sale::PAYMENT_STATUS_PAID);
                    }])
                    ->withSum(['sales as total_revenue' => function (Builder $query) {
                        $query->where('payment_status', Sale::PAYMENT_STATUS_PAID);
                    }], 'total_price')
                    ->orderByDesc('total_sales')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Товар')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('productTemplate.name')
                    ->label('Шаблон')
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Остаток')
                    ->sortable()
                    ->badge()
                    ->color(function (Product $record): string {
                        if ($record->quantity <= 0) {
                            return 'danger';
                        }
                        if ($record->quantity <= 10) {
                            return 'warning';
                        }

                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('total_sales')
                    ->label('Продажи')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Выручка')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('producer')
                    ->label('Производитель')
                    ->sortable()
                    ->placeholder('Не указан'),
            ])
            ->emptyStateHeading('Нет данных')
            ->emptyStateDescription('Недостаточно данных для определения популярности товаров.')
            ->paginated(false);
    }
}
