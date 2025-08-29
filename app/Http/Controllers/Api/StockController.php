<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Получить список остатков товаров
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->select([
                'product_template_id',
                'warehouse_id',
                DB::raw('COALESCE(producer, "null") as producer'),
                DB::raw('CONCAT(product_template_id, "_", warehouse_id, "_", COALESCE(producer, "null")) as id'),
                DB::raw('SUM(quantity - COALESCE(sold_quantity, 0)) as total_quantity'),
                DB::raw('SUM(calculated_volume * quantity) as total_volume'),
                DB::raw('MIN(name) as name'),
                DB::raw('MIN(status) as status'),
                DB::raw('MIN(is_active) as is_active')
            ])
            ->where('is_active', 1)
            ->groupBy('product_template_id', 'warehouse_id', DB::raw('COALESCE(producer, "null")'))
            ->having('total_quantity', '>', 0);

        // Фильтрация по складу
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $stocks = $query->with(['productTemplate', 'warehouse'])
            ->orderBy('name')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $stocks->items(),
            'pagination' => [
                'current_page' => $stocks->currentPage(),
                'last_page' => $stocks->lastPage(),
                'per_page' => $stocks->perPage(),
                'total' => $stocks->total(),
            ]
        ]);
    }

    /**
     * Получить детальную информацию об остатке
     */
    public function show(string $id): JsonResponse
    {
        // Разбираем составной ID
        $parts = explode('_', $id);
        if (count($parts) !== 3) {
            return response()->json([
                'success' => false,
                'message' => 'Неверный формат ID'
            ], 400);
        }

        [$productTemplateId, $warehouseId, $producer] = $parts;
        if ($producer === 'null') {
            $producer = null;
        }

        $stock = Product::query()
            ->select([
                'product_template_id',
                'warehouse_id',
                DB::raw('COALESCE(producer, "null") as producer'),
                DB::raw('SUM(quantity - COALESCE(sold_quantity, 0)) as total_quantity'),
                DB::raw('SUM(calculated_volume * quantity) as total_volume'),
                DB::raw('MIN(name) as name'),
                DB::raw('MIN(status) as status'),
                DB::raw('MIN(is_active) as is_active')
            ])
            ->where('product_template_id', $productTemplateId)
            ->where('warehouse_id', $warehouseId)
            ->where('producer', $producer)
            ->where('is_active', 1)
            ->groupBy('product_template_id', 'warehouse_id', DB::raw('COALESCE(producer, "null")'))
            ->with(['productTemplate', 'warehouse'])
            ->first();

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'Остаток не найден'
            ], 404);
        }

        // Получаем детальную информацию о товарах
        $products = Product::where('product_template_id', $productTemplateId)
            ->where('warehouse_id', $warehouseId)
            ->where('producer', $producer)
            ->where('is_active', 1)
            ->where('quantity', '>', 0)
            ->with(['productTemplate', 'warehouse'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stock' => $stock,
                'products' => $products
            ]
        ]);
    }
}
