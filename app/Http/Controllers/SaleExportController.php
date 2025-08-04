<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class SaleExportController extends Controller
{
    public function export(Request $request)
    {
        $user = Auth::user();
        
        // Получаем продажи с учетом прав доступа
        $query = Sale::with(['product', 'warehouse', 'user']);
        
        if ($user->role !== 'admin') {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }
        
        // Применяем фильтры
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        if ($request->has('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        }
        
        if ($request->has('date_from')) {
            $query->where('sale_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->where('sale_date', '<=', $request->date_to);
        }
        
        $sales = $query->get();
        
        // Формируем CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sales_' . date('Y-m-d') . '.csv"',
        ];
        
        $callback = function() use ($sales) {
            $file = fopen('php://output', 'w');
            
            // Заголовки
            fputcsv($file, [
                'ID',
                'Номер продажи',
                'Товар',
                'Склад',
                'Клиент',
                'Количество',
                'Цена за ед.',
                'Сумма без НДС',
                'НДС',
                'Общая сумма',
                'Способ оплаты',
                'Статус оплаты',
                'Статус доставки',
                'Дата продажи',
                'Дата доставки',
                'Продавец',
                'Заметки'
            ]);
            
            // Данные
            foreach ($sales as $sale) {
                fputcsv($file, [
                    $sale->id,
                    $sale->sale_number,
                    $sale->product?->name ?? 'Не указан',
                    $sale->warehouse?->name ?? 'Не указан',
                    $sale->customer_name ?? 'Не указан',
                    $sale->quantity,
                    $sale->unit_price,
                    $sale->price_without_vat,
                    $sale->vat_amount,
                    $sale->total_price,
                    $sale->getPaymentMethodLabel(),
                    $sale->getPaymentStatusLabel(),
                    $sale->getDeliveryStatusLabel(),
                    $sale->sale_date->format('Y-m-d'),
                    $sale->delivery_date?->format('Y-m-d') ?? 'Не указана',
                    $sale->user?->name ?? 'Не указан',
                    $sale->notes ?? ''
                ]);
            }
            
            fclose($file);
        };
        
        return Response::stream($callback, 200, $headers);
    }
} 