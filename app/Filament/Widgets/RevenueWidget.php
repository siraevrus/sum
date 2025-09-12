<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\Widget;
class RevenueWidget extends Widget
{
    protected static string $view = 'filament.widgets.revenue-widget';
    
    protected static ?int $sort = 3; // Перед PopularProducts (у которого sort = 4)
    
    protected int|string|array $columnSpan = 'full';
    
    public string $period = 'day';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function getViewData(): array
    {
        return [
            'revenueData' => $this->getRevenueData(),
            'period' => $this->period,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ];
    }

    public function getRevenueData(): array
    {
        $dateRange = $this->getDateRange();
        
        $salesQuery = Sale::query()
            ->where('payment_status', Sale::PAYMENT_STATUS_PAID)
            ->where('is_active', true);

        // Применяем фильтрацию по датам в зависимости от периода
        if ($this->period === 'day') {
            $salesQuery->whereDate('sale_date', $dateRange[0]);
        } else {
            $salesQuery->whereBetween('sale_date', [
                $dateRange[0] . ' 00:00:00',
                $dateRange[1] . ' 23:59:59'
            ]);
        }

        // Группируем по валютам и суммируем
        $results = $salesQuery
            ->selectRaw('currency, SUM(total_price) as total')
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        // Формируем данные для всех валют
        $currencies = ['USD', 'RUB', 'UZS'];
        $revenueData = [];

        foreach ($currencies as $currency) {
            $amount = $results->get($currency)?->total ?? 0;
            $revenueData[$currency] = [
                'amount' => $amount,
                'formatted' => $this->formatCurrency($amount, $currency)
            ];
        }

        return $revenueData;
    }

    private function getDateRange(): array
    {
        $today = now()->format('Y-m-d');
        
        return match ($this->period) {
            'day' => [$today, $today],
            'week' => [now()->subDays(6)->format('Y-m-d'), $today],
            'month' => [now()->subDays(29)->format('Y-m-d'), $today],
            'custom' => [
                $this->dateFrom ?? $today,
                $this->dateTo ?? $today
            ],
            default => [$today, $today]
        };
    }

    private function formatCurrency(float $amount, string $currency): string
    {
        $formatted = number_format($amount, 0, '.', ' ');
        
        return match ($currency) {
            'USD' => $formatted . '$',
            'RUB' => $formatted . ' ₽',
            'UZS' => $formatted . ' Сум',
            default => $formatted
        };
    }

    public function updatedPeriod(): void
    {
        if ($this->period !== 'custom') {
            $this->dateFrom = null;
            $this->dateTo = null;
        }
    }
}
