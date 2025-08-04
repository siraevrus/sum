<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class ProductExportController extends Controller
{
    public function export(Request $request)
    {
        $user = Auth::user();
        
        // Получаем товары с учетом прав доступа
        $query = Product::with(['template', 'warehouse', 'creator']);
        
        if ($user->role !== 'admin') {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }
        
        // Применяем фильтры
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        if ($request->has('product_template_id')) {
            $query->where('product_template_id', $request->product_template_id);
        }
        
        if ($request->has('producer')) {
            $query->where('producer', $request->producer);
        }
        
        if ($request->has('in_stock')) {
            $query->where('quantity', '>', 0);
        }
        
        $products = $query->get();
        
        // Формируем CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="products_' . date('Y-m-d') . '.csv"',
        ];
        
        $callback = function() use ($products) {
            $file = fopen('php://output', 'w');
            
            // Заголовки
            fputcsv($file, [
                'ID',
                'Название',
                'Описание',
                'Шаблон',
                'Склад',
                'Производитель',
                'Количество',
                'Объем (м³)',
                'Дата поступления',
                'Статус',
                'Создатель',
                'Дата создания'
            ]);
            
            // Данные
            foreach ($products as $product) {
                fputcsv($file, [
                    $product->id,
                    $product->name,
                    $product->description,
                    $product->template?->name ?? 'Не указан',
                    $product->warehouse?->name ?? 'Не указан',
                    $product->producer ?? 'Не указан',
                    $product->quantity,
                    $product->calculated_volume,
                    $product->arrival_date?->format('Y-m-d') ?? 'Не указана',
                    $product->is_active ? 'Активен' : 'Неактивен',
                    $product->creator?->name ?? 'Не указан',
                    $product->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };
        
        return Response::stream($callback, 200, $headers);
    }
} 