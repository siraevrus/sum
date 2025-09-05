<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Product;
use App\Models\Product as ProductModel;
use App\Models\Request;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Компаний', Company::where('is_archived', false)->count())
                ->description('Активные компании')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),

            Stat::make('Сотрудников', User::where('is_blocked', false)->count())
                ->description('Активные сотрудники')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Складов', Warehouse::where('is_active', true)->count())
                ->description('Активные склады')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color('warning'),

            Stat::make('Товаров', Product::count())
                ->description('Всего товаров')
                ->descriptionIcon('heroicon-m-cube')
                ->color('success'),

            Stat::make('В пути', ProductModel::where('status', ProductModel::STATUS_IN_TRANSIT)
                ->where('is_active', true)
                ->count())
                ->description('Товары в доставке')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),

            Stat::make('Запросы', Request::byStatus(Request::STATUS_PENDING)->count())
                ->description('Ожидают рассмотрения')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),

            Stat::make('Продажи USD', number_format(Sale::where('sale_date', '>=', now()->startOfDay())
                ->where('payment_status', Sale::PAYMENT_STATUS_PAID)
                ->where('currency', 'USD')
                ->sum('total_price'), 0, ',', ' ').' $')
                ->description('Выручка за сегодня (USD)')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Продажи RUB', number_format(Sale::where('sale_date', '>=', now()->startOfDay())
                ->where('payment_status', Sale::PAYMENT_STATUS_PAID)
                ->where('currency', 'RUB')
                ->sum('total_price'), 0, ',', ' ').' ₽')
                ->description('Выручка за сегодня (RUB)')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Продажи KGS', number_format(Sale::where('sale_date', '>=', now()->startOfDay())
                ->where('payment_status', Sale::PAYMENT_STATUS_PAID)
                ->where('currency', 'KGS')
                ->sum('total_price'), 0, ',', ' ').' сом')
                ->description('Выручка за сегодня (KGS)')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}
