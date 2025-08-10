<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Инфопанель';

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        return $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
    }

    public function mount(): void
    {
        $user = Auth::user();
        if ($user && method_exists($user, 'isAdmin') && !$user->isAdmin()) {
            $target = $this->resolveFirstAccessibleRoute();
            if ($target) {
                $this->redirect($target);
                return;
            }
        }

        parent::mount();
    }

    protected function resolveFirstAccessibleRoute(): ?string
    {
        $candidates = [
            \App\Filament\Resources\StockResource::class => 'filament.admin.resources.stocks.index',
            \App\Filament\Resources\ProductResource::class => 'filament.admin.resources.products.index',
            \App\Filament\Resources\RequestResource::class => 'filament.admin.resources.requests.index',
            \App\Filament\Resources\SaleResource::class => 'filament.admin.resources.sales.index',
            \App\Filament\Resources\ReceiptResource::class => 'filament.admin.resources.receipts.index',
            \App\Filament\Resources\WarehouseResource::class => 'filament.admin.resources.warehouses.index',
            \App\Filament\Resources\CompanyResource::class => 'filament.admin.resources.companies.index',
            \App\Filament\Resources\UserResource::class => 'filament.admin.resources.users.index',
        ];

        foreach ($candidates as $resourceClass => $routeName) {
            if (class_exists($resourceClass) && method_exists($resourceClass, 'canViewAny')) {
                try {
                    if ($resourceClass::canViewAny()) {
                        return route($routeName);
                    }
                } catch (\Throwable $e) {
                    // ignore and continue
                }
            }
        }

        return null;
    }
}


