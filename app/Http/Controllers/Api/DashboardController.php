<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Product;
use App\Models\Request as RequestModel;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Return aggregated dashboard summary for the mobile app.
     */
    public function summary(): JsonResponse
    {
        $companiesActive = Company::query()->where('is_archived', false)->count();
        $employeesActive = User::query()->where('is_blocked', false)->count();
        $warehousesActive = Warehouse::query()->where('is_active', true)->count();

        $productsTotal = Product::query()->count();

        $productsInTransit = Product::query()
            ->when(defined(Product::class.'::STATUS_IN_TRANSIT'), function ($q) {
                /** @var \Illuminate\Database\Eloquent\Builder $q */
                $q->where('status', Product::STATUS_IN_TRANSIT);
            }, function ($q) {
                /** @var \Illuminate\Database\Eloquent\Builder $q */
                $q->where('status', 'in_transit');
            })
            ->when(schema_has_column('products', 'is_active'), fn ($q) => $q->where('is_active', true))
            ->count();

        $requestsPending = RequestModel::query()
            ->when(method_exists(RequestModel::class, 'byStatus'), function ($q) {
                /** @var \Illuminate\Database\Eloquent\Builder $q */
                $status = defined(RequestModel::class.'::STATUS_PENDING') ? RequestModel::STATUS_PENDING : 'pending';
                $q->byStatus($status);
            }, function ($q) {
                /** @var \Illuminate\Database\Eloquent\Builder $q */
                $q->where('status', 'pending');
            })
            ->count();

        $latestSales = Sale::query()
            ->with(['product'])
            ->when(schema_has_column('sales', 'sale_date'), fn ($q) => $q->latest('sale_date'), fn ($q) => $q->latest())
            ->limit(10)
            ->get()
            ->map(function (Sale $sale) {
                $total = $sale->total_amount ?? $sale->total_price ?? null;
                $client = $sale->client_name ?? $sale->customer_name ?? null;

                return [
                    'id' => $sale->id,
                    'product_name' => optional($sale->product)->name,
                    'client_name' => $client,
                    'quantity' => $sale->quantity,
                    'total_amount' => $total,
                    'sale_date' => $sale->sale_date ?? ($sale->created_at ? $sale->created_at->toDateTimeString() : null),
                ];
            });

        return response()->json([
            'companies_active' => $companiesActive,
            'employees_active' => $employeesActive,
            'warehouses_active' => $warehousesActive,
            'products_total' => $productsTotal,
            'products_in_transit' => $productsInTransit,
            'requests_pending' => $requestsPending,
            'latest_sales' => $latestSales,
        ]);
    }
}

if (! function_exists('schema_has_column')) {
    /**
     * Safe schema column check avoiding runtime issues in production.
     */
    function schema_has_column(string $table, string $column): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
