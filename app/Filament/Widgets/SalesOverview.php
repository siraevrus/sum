<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverview extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        return [
            Stat::make('Продажи сегодня', Sale::where('sale_date', '>=', $today)->count())
                ->description('Количество продаж за сегодня')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),

            Stat::make('Выручка сегодня', Sale::where('sale_date', '>=', $today)
                ->where('payment_status', Sale::PAYMENT_STATUS_PAID)
                ->sum('total_price'))
                ->description('Общая выручка за сегодня')
                ->descriptionIcon('heroicon-m-currency-ruble')
                ->color('success')
                ->formatStateUsing(fn (float $state): string => number_format($state, 0, ',', ' ') . ' ₽'),

            Stat::make('Выручка за месяц', Sale::where('sale_date', '>=', $thisMonth)
                ->where('payment_status', Sale::PAYMENT_STATUS_PAID)
                ->sum('total_price'))
                ->description('Общая выручка за текущий месяц')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info')
                ->formatStateUsing(fn (float $state): string => number_format($state, 0, ',', ' ') . ' ₽'),

            Stat::make('Средний чек', Sale::where('payment_status', Sale::PAYMENT_STATUS_PAID)
                ->avg('total_price'))
                ->description('Средняя сумма продажи')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning')
                ->formatStateUsing(fn (float $state): string => number_format($state, 0, ',', ' ') . ' ₽'),

            Stat::make('Ожидают оплаты', Sale::where('payment_status', Sale::PAYMENT_STATUS_PENDING)->count())
                ->description('Продажи в ожидании оплаты')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('В доставке', Sale::where('delivery_status', Sale::DELIVERY_STATUS_IN_PROGRESS)->count())
                ->description('Продажи в процессе доставки')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),
        ];
    }
} 