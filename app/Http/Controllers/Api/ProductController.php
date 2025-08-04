<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Получение списка товаров
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Product::with(['template', 'warehouse', 'creator'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('producer', 'like', "%{$search}%");
            })
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->template_id, function ($query, $templateId) {
                $query->where('product_template_id', $templateId);
            })
            ->when($request->producer, function ($query, $producer) {
                $query->where('producer', $producer);
            })
            ->when($request->in_stock, function ($query) {
                $query->where('quantity', '>', 0);
            })
            ->when($request->low_stock, function ($query) {
                $query->where('quantity', '<=', 10)->where('quantity', '>', 0);
            })
            ->when($request->active, function ($query) {
                $query->where('is_active', true);
            });

        // Применяем права доступа
        if ($user->role !== 'admin') {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $products = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Получение товара по ID
     */
    public function show(Product $product): JsonResponse
    {
        $user = Auth::user();
        
        // Проверяем права доступа
        if ($user->role !== 'admin' && $product->warehouse->company_id !== $user->company_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        return response()->json([
            'data' => $product->load(['template', 'warehouse', 'creator']),
        ]);
    }

    /**
     * Создание нового товара
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_template_id' => 'required|exists:product_templates,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'attributes' => 'required|array',
            'quantity' => 'required|integer|min:0',
            'producer' => 'nullable|string|max:255',
            'arrival_date' => 'required|date',
            'is_active' => 'boolean',
        ]);

        $user = Auth::user();
        
        // Проверяем права доступа к складу
        if ($user->role !== 'admin') {
            $warehouse = Warehouse::find($request->warehouse_id);
            if (!$warehouse || $warehouse->company_id !== $user->company_id) {
                return response()->json(['message' => 'Доступ к складу запрещен'], 403);
            }
        }

        $product = Product::create([
            'product_template_id' => $request->product_template_id,
            'warehouse_id' => $request->warehouse_id,
            'created_by' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'attributes' => $request->attributes,
            'quantity' => $request->quantity,
            'producer' => $request->producer,
            'arrival_date' => $request->arrival_date,
            'is_active' => $request->get('is_active', true),
        ]);

        // Рассчитываем объем
        $product->updateCalculatedVolume();

        return response()->json([
            'message' => 'Товар успешно создан',
            'data' => $product->load(['template', 'warehouse', 'creator']),
        ], 201);
    }

    /**
     * Обновление товара
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $user = Auth::user();
        
        // Проверяем права доступа
        if ($user->role !== 'admin' && $product->warehouse->company_id !== $user->company_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'attributes' => 'sometimes|array',
            'quantity' => 'sometimes|integer|min:0',
            'producer' => 'nullable|string|max:255',
            'arrival_date' => 'sometimes|date',
            'is_active' => 'boolean',
        ]);

        $product->update($request->only([
            'name', 'description', 'attributes', 'quantity', 
            'producer', 'arrival_date', 'is_active'
        ]));

        // Пересчитываем объем если изменились атрибуты
        if ($request->has('attributes')) {
            $product->updateCalculatedVolume();
        }

        return response()->json([
            'message' => 'Товар успешно обновлен',
            'data' => $product->load(['template', 'warehouse', 'creator']),
        ]);
    }

    /**
     * Удаление товара
     */
    public function destroy(Product $product): JsonResponse
    {
        $user = Auth::user();
        
        // Проверяем права доступа
        if ($user->role !== 'admin' && $product->warehouse->company_id !== $user->company_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $product->delete();

        return response()->json([
            'message' => 'Товар успешно удален',
        ]);
    }

    /**
     * Получение статистики по товарам
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();
        
        // Кешируем статистику на 5 минут
        $cacheKey = "product_stats_{$user->id}";
        
        $stats = Cache::remember($cacheKey, 300, function () use ($user) {
            $query = Product::query();
            
            // Применяем права доступа
            if ($user->role !== 'admin') {
                $query->whereHas('warehouse', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            }

            return [
                'total_products' => $query->count(),
                'active_products' => $query->where('is_active', true)->count(),
                'in_stock' => $query->where('quantity', '>', 0)->count(),
                'low_stock' => $query->where('quantity', '<=', 10)->where('quantity', '>', 0)->count(),
                'out_of_stock' => $query->where('quantity', '<=', 0)->count(),
                'total_quantity' => $query->sum('quantity'),
                'total_volume' => $query->sum('calculated_volume'),
            ];
        });

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Получение популярных товаров
     */
    public function popular(): JsonResponse
    {
        $user = Auth::user();
        
        $query = Product::with(['template', 'warehouse'])
            ->withCount(['sales as total_sales'])
            ->withSum(['sales as total_revenue'], 'total_price')
            ->orderByDesc('total_sales')
            ->limit(10);

        // Применяем права доступа
        if ($user->role !== 'admin') {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $products = $query->get();

        return response()->json([
            'data' => $products,
        ]);
    }
} 