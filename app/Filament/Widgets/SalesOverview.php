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

            Stat::make('Выручка сегодня', number_format(Sale::where('sale_date', '>=', $today)
                ->where('payment_status', Sale::PAYMENT_STATUS_PAID)
                ->sum('total_price'), 0, ',', ' ') . ' ₽')
                ->description('Общая выручка за сегодня')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Выручка за месяц', number_format(Sale::where('sale_date', '>=', $thisMonth)
                ->where('payment_status', Sale::PAYMENT_STATUS_PAID)
                ->sum('total_price'), 0, ',', ' ') . ' ₽')
                ->description('Общая выручка за текущий месяц')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Средний чек', number_format(Sale::where('payment_status', Sale::PAYMENT_STATUS_PAID)
                ->avg('total_price'), 0, ',', ' ') . ' ₽')
                ->description('Средняя сумма продажи')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning'),

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