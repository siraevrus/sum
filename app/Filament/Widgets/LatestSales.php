<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;

class LatestSales extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Последние продажи';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Sale::query()
                    ->with(['product', 'warehouse', 'user'])
                    ->latest('sale_date')
                    ->limit(10)
            )
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

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Сумма')
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

                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Дата продажи')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Продавец')
                    ->sortable(),
            ])
            ->paginated(false);
    }
} 