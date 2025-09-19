<?php

namespace App\Filament\Resources\BuyerResource\Pages;

use App\Filament\Resources\BuyerResource;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class PurchasesByBuyer extends Page
{
    protected static string $resource = BuyerResource::class;

    protected static string $view = 'filament.resources.buyer-resource.pages.purchases-by-buyer';

    protected $phone;

    protected $customer_name;

    protected $purchases;

    public function mount(): void
    {
        $this->phone = request()->get('phone');
        $this->customer_name = DB::table('sales')
            ->where('customer_phone', $this->phone)
            ->value('customer_name');
        $this->purchases = DB::table('sales')
            ->where('customer_phone', $this->phone)
            ->orderByDesc('sale_date')
            ->get();
    }

    public function getViewData(): array
    {
        return [
            'phone' => $this->phone,
            'customer_name' => $this->customer_name,
            'purchases' => $this->purchases,
        ];
    }
}
