<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SaleController extends Controller
{
    /**
     * Получение списка продаж
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Sale::with(['product', 'warehouse', 'user'])
            ->when($request->search, function ($query, $search) {
                $query->where('sale_number', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_phone', 'like', "%{$search}%");
            })
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->payment_status, function ($query, $status) {
                $query->where('payment_status', $status);
            })
            ->when($request->delivery_status, function ($query, $status) {
                $query->where('delivery_status', $status);
            })
            ->when($request->payment_method, function ($query, $method) {
                $query->where('payment_method', $method);
            })
            ->when($request->date_from, function ($query, $date) {
                $query->where('sale_date', '>=', $date);
            })
            ->when($request->date_to, function ($query, $date) {
                $query->where('sale_date', '<=', $date);
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

        $sales = $query->orderBy('created_at', 'desc')
                       ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $sales->items(),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ],
        ]);
    }

    /**
     * Получение продажи по ID
     */
    public function show(Sale $sale): JsonResponse
    {
        $user = Auth::user();
        
        // Проверяем права доступа
        if ($user->role !== 'admin' && $sale->warehouse->company_id !== $user->company_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        return response()->json([
            'data' => $sale->load(['product', 'warehouse', 'user']),
        ]);
    }

    /**
     * Создание новой продажи
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_address' => 'nullable|string',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'payment_method' => 'required|string|in:cash,card,bank_transfer,other',
            'payment_status' => 'nullable|string|in:pending,paid,partially_paid,cancelled',
            'delivery_status' => 'nullable|string|in:pending,in_progress,delivered,cancelled',
            'notes' => 'nullable|string',
            'sale_date' => 'required|date',
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

        // Проверяем наличие товара
        $product = Product::find($request->product_id);
        if (!$product || $product->quantity < $request->quantity) {
            return response()->json(['message' => 'Недостаточно товара на складе'], 400);
        }

        $sale = Sale::create([
            'product_id' => $request->product_id,
            'warehouse_id' => $request->warehouse_id,
            'user_id' => $user->id,
            'sale_number' => Sale::generateSaleNumber(),
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_email' => $request->customer_email,
            'customer_address' => $request->customer_address,
            'quantity' => $request->quantity,
            'unit_price' => $request->unit_price,
            'vat_rate' => $request->get('vat_rate', 20.00),
            'payment_method' => $request->payment_method,
            'payment_status' => $request->get('payment_status', Sale::PAYMENT_STATUS_PENDING),
            'delivery_status' => $request->get('delivery_status', Sale::DELIVERY_STATUS_PENDING),
            'notes' => $request->notes,
            'sale_date' => $request->sale_date,
            'is_active' => $request->get('is_active', true),
        ]);

        // Рассчитываем цены
        $sale->calculatePrices();
        $sale->save();

        return response()->json([
            'message' => 'Продажа успешно создана',
            'data' => $sale->load(['product', 'warehouse', 'user']),
        ], 201);
    }

    /**
     * Обновление продажи
     */
    public function update(Request $request, Sale $sale): JsonResponse
    {
        $user = Auth::user();
        
        // Проверяем права доступа
        if ($user->role !== 'admin' && $sale->warehouse->company_id !== $user->company_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_address' => 'nullable|string',
            'quantity' => 'sometimes|integer|min:1',
            'unit_price' => 'sometimes|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'payment_method' => 'sometimes|string|in:cash,card,bank_transfer,other',
            'payment_status' => 'sometimes|string|in:pending,paid,partially_paid,cancelled',
            'delivery_status' => 'sometimes|string|in:pending,in_progress,delivered,cancelled',
            'notes' => 'nullable|string',
            'sale_date' => 'sometimes|date',
            'delivery_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $sale->update($request->only([
            'customer_name', 'customer_phone', 'customer_email', 'customer_address',
            'quantity', 'unit_price', 'vat_rate', 'payment_method', 'payment_status',
            'delivery_status', 'notes', 'sale_date', 'delivery_date', 'is_active'
        ]));

        // Пересчитываем цены если изменились количество или цена
        if ($request->has('quantity') || $request->has('unit_price') || $request->has('vat_rate')) {
            $sale->calculatePrices();
            $sale->save();
        }

        return response()->json([
            'message' => 'Продажа успешно обновлена',
            'data' => $sale->load(['product', 'warehouse', 'user']),
        ]);
    }

    /**
     * Удаление продажи
     */
    public function destroy(Sale $sale): JsonResponse
    {
        $user = Auth::user();
        
        // Проверяем права доступа
        if ($user->role !== 'admin' && $sale->warehouse->company_id !== $user->company_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $sale->delete();

        return response()->json([
            'message' => 'Продажа успешно удалена',
        ]);
    }

    /**
     * Оформление продажи (списание товара)
     */
    public function process(Sale $sale): JsonResponse
    {
        $user = Auth::user();
        
        // Проверяем права доступа
        if ($user->role !== 'admin' && $sale->warehouse->company_id !== $user->company_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        if (!$sale->canBeSold()) {
            return response()->json(['message' => 'Недостаточно товара для продажи'], 400);
        }

        if ($sale->processSale()) {
            return response()->json([
                'message' => 'Продажа успешно оформлена',
                'data' => $sale->load(['product', 'warehouse', 'user']),
            ]);
        }

        return response()->json(['message' => 'Ошибка при оформлении продажи'], 500);
    }

    /**
     * Отмена продажи
     */
    public function cancel(Sale $sale): JsonResponse
    {
        $user = Auth::user();
        
        // Проверяем права доступа
        if ($user->role !== 'admin' && $sale->warehouse->company_id !== $user->company_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        if ($sale->cancelSale()) {
            return response()->json([
                'message' => 'Продажа успешно отменена',
                'data' => $sale->load(['product', 'warehouse', 'user']),
            ]);
        }

        return response()->json(['message' => 'Ошибка при отмене продажи'], 500);
    }

    /**
     * Получение статистики по продажам
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();
        
        // Кешируем статистику на 2 минуты
        $cacheKey = "sale_stats_{$user->id}";
        
        $stats = Cache::remember($cacheKey, 120, function () use ($user) {
            $query = Sale::query();
            
            // Применяем права доступа
            if ($user->role !== 'admin') {
                $query->whereHas('warehouse', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            }

            $today = now()->startOfDay();
            $thisMonth = now()->startOfMonth();

            return [
                'total_sales' => $query->count(),
                'paid_sales' => $query->where('payment_status', Sale::PAYMENT_STATUS_PAID)->count(),
                'pending_payments' => $query->where('payment_status', Sale::PAYMENT_STATUS_PENDING)->count(),
                'today_sales' => $query->where('sale_date', '>=', $today)->count(),
                'month_revenue' => $query->where('sale_date', '>=', $thisMonth)
                                       ->where('payment_status', Sale::PAYMENT_STATUS_PAID)
                                       ->sum('total_price'),
                'total_revenue' => $query->where('payment_status', Sale::PAYMENT_STATUS_PAID)
                                       ->sum('total_price'),
                'total_quantity' => $query->sum('quantity'),
            ];
        });

        return response()->json([
            'data' => $stats,
        ]);
    }
} 