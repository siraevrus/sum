<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Product;
use App\Models\Product as ProductModel;
use App\Models\Request;
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
        ];
    }
}
