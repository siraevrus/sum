<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductInTransit;
use App\Models\Request;
use App\Models\Sale;
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

            Stat::make('Складов', \App\Models\Warehouse::where('is_active', true)->count())
                ->description('Активные склады')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color('warning'),

            Stat::make('Товаров', Product::count())
                ->description('Всего товаров')
                ->descriptionIcon('heroicon-m-cube')
                ->color('success'),

            Stat::make('Остатки', Product::where('quantity', '>', 0)->sum('quantity'))
                ->description('Общее количество')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('info'),

            Stat::make('В пути', ProductInTransit::inTransit()->count())
                ->description('Товары в доставке')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),

            Stat::make('Запросы', Request::pending()->count())
                ->description('Ожидают рассмотрения')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),

            Stat::make('Продажи', Sale::paid()->sum('total_price'))
                ->description('Общая выручка')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->formatStateUsing(fn (float $state): string => number_format($state, 0, ',', ' ') . ' ₽'),
        ];
    }
}
