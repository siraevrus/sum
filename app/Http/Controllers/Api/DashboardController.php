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
use Illuminate\Http\Request;

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

    /**
     * Revenue data by currency for a period (day|week|month|custom).
     */
    public function revenue(Request $request): JsonResponse
    {
        $period = $request->query('period', 'day');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        [$from, $to] = $this->resolveDateRange($period, $dateFrom, $dateTo);

        $salesQuery = \App\Models\Sale::query()
            ->where('payment_status', \App\Models\Sale::PAYMENT_STATUS_PAID)
            ->when(schema_has_column('sales', 'is_active'), fn ($q) => $q->where('is_active', true));

        if ($period === 'day') {
            $salesQuery->whereDate('sale_date', $from);
        } else {
            $salesQuery->whereBetween('sale_date', [
                $from.' 00:00:00',
                $to.' 23:59:59',
            ]);
        }

        $results = $salesQuery
            ->selectRaw('currency, SUM(total_price) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        $currencies = ['USD', 'RUB', 'UZS'];
        $data = [];
        foreach ($currencies as $currency) {
            $amount = (float) ($results[$currency] ?? 0);
            $data[$currency] = [
                'amount' => $amount,
                'formatted' => $this->formatCurrency($amount, $currency),
            ];
        }

        return response()->json([
            'period' => $period,
            'date_from' => $from,
            'date_to' => $to,
            'revenue' => $data,
        ]);
    }

    private function resolveDateRange(string $period, ?string $dateFrom, ?string $dateTo): array
    {
        $today = now()->format('Y-m-d');

        return match ($period) {
            'day' => [$today, $today],
            'week' => [now()->subDays(6)->format('Y-m-d'), $today],
            'month' => [now()->subDays(29)->format('Y-m-d'), $today],
            'custom' => [
                $dateFrom ?: $today,
                $dateTo ?: $today,
            ],
            default => [$today, $today],
        };
    }

    private function formatCurrency(float $amount, string $currency): string
    {
        $formatted = number_format($amount, 0, '.', ' ');

        return match ($currency) {
            'USD' => $formatted.'$',
            'RUB' => $formatted.' ₽',
            'UZS' => $formatted.' Сум',
            default => $formatted,
        };
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
