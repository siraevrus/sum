<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        // Применяем права доступа: не админ видит только свой склад
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $sales = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $sales->items(),
            'links' => [
                'first' => $sales->url(1),
                'last' => $sales->url($sales->lastPage()),
                'prev' => $sales->previousPageUrl(),
                'next' => $sales->nextPageUrl(),
            ],
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
    public function showById(int $id): JsonResponse
    {
        $user = Auth::user();
        $sale = Sale::with(['product', 'warehouse', 'user'])->find($id);
        if (! $sale) {
            return response()->json(['message' => 'Продажа не найдена'], 404);
        }

        if (! $user->isAdmin() && $user->warehouse_id !== $sale->warehouse_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        return response()->json($sale);
    }

    /**
     * Создание новой продажи
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'customer_name' => 'required|string|max:255',
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
        if (! $user->isAdmin()) {
            if (! $user->warehouse_id || (int) $request->warehouse_id !== (int) $user->warehouse_id) {
                return response()->json(['message' => 'Доступ к складу запрещен'], 403);
            }
        }

        // Проверяем наличие товара
        $product = Product::find($request->product_id);
        if (! $product || $product->quantity < $request->quantity) {
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
            'price_without_vat' => $request->unit_price * $request->quantity,
            'vat_rate' => $request->get('vat_rate', 20.00),
            'vat_amount' => ($request->unit_price * $request->quantity) * ($request->get('vat_rate', 20.00) / 100),
            'total_price' => ($request->unit_price * $request->quantity) * (1 + ($request->get('vat_rate', 20.00) / 100)),
            'payment_method' => $request->payment_method,
            'payment_status' => $request->get('payment_status', Sale::PAYMENT_STATUS_PENDING),
            'delivery_status' => $request->get('delivery_status', Sale::DELIVERY_STATUS_PENDING),
            'notes' => $request->notes,
            'sale_date' => $request->sale_date,
            'is_active' => $request->get('is_active', true),
        ]);

        // Пересчёт не требуется, значения уже установлены

        return response()->json([
            'message' => 'Продажа создана',
            'sale' => $sale->load(['product', 'warehouse', 'user']),
        ], 201);
    }

    /**
     * Обновление продажи
     */
    public function update(Request $request, Sale $sale): JsonResponse
    {
        $user = Auth::user();

        // Проверяем права доступа
        if (! $user->isAdmin() && $user->warehouse_id !== $sale->warehouse_id) {
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
            'delivery_status', 'notes', 'sale_date', 'delivery_date', 'is_active',
        ]));

        // Пересчитываем цены если изменились количество или цена
        if ($request->has('quantity') || $request->has('unit_price') || $request->has('vat_rate')) {
            $sale->calculatePrices();
            $sale->save();
        }

        return response()->json([
            'message' => 'Продажа обновлена',
            'sale' => $sale->load(['product', 'warehouse', 'user']),
        ]);
    }

    /**
     * Удаление продажи
     */
    public function destroy(Sale $sale): JsonResponse
    {
        $user = Auth::user();

        // Проверяем права доступа
        if (! $user->isAdmin() && $user->company_id && $sale->warehouse->company_id !== $user->company_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $sale->delete();

        return response()->json([
            'message' => 'Продажа удалена',
        ]);
    }

    /**
     * Оформление продажи (списание товара)
     */
    public function process(Sale $sale): JsonResponse
    {
        $user = Auth::user();

        // Проверяем права доступа
        if (! $user->isAdmin() && $user->warehouse_id !== $sale->warehouse_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        // Проверим остаток по товару свежим запросом
        $product = Product::find($sale->product_id);
        if (! $product) {
            return response()->json(['message' => 'Товар не найден'], 404);
        }
        if ($product->quantity < $sale->quantity) {
            return response()->json(['message' => 'Недостаточно товара на складе'], 400);
        }

        if ($sale->processSale()) {
            return response()->json([
                'message' => 'Продажа оформлена',
                'sale' => $sale->load(['product', 'warehouse', 'user']),
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
        if (! $user->isAdmin() && $user->warehouse_id !== $sale->warehouse_id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        if ($sale->cancelSale()) {
            return response()->json([
                'message' => 'Продажа отменена',
                'sale' => $sale->load(['product', 'warehouse', 'user']),
            ]);
        }

        return response()->json(['message' => 'Ошибка при отмене продажи'], 500);
    }

    /**
     * Получение статистики продаж
     */
    public function stats(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Sale::query();

        // Применяем права доступа: не админ видит только свой склад
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Фильтры по датам
        if ($request->date_from) {
            $query->where('sale_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('sale_date', '<=', $request->date_to);
        }

        // Фильтры по статусам
        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->delivery_status) {
            $query->where('delivery_status', $request->delivery_status);
        }

        $stats = [
            'total_sales' => (clone $query)->count(),
            'paid_sales' => (clone $query)->where('payment_status', Sale::PAYMENT_STATUS_PAID)->count(),
            'pending_payments' => (clone $query)->where('payment_status', Sale::PAYMENT_STATUS_PENDING)->count(),
            'today_sales' => (clone $query)->whereDate('sale_date', today())->count(),
            'month_revenue' => (clone $query)->where('payment_status', Sale::PAYMENT_STATUS_PAID)
                ->whereMonth('sale_date', now()->month)
                ->whereYear('sale_date', now()->year)
                ->sum('total_price'),
            'total_revenue' => (clone $query)->where('payment_status', Sale::PAYMENT_STATUS_PAID)->sum('total_price'),
            'total_quantity' => (clone $query)->sum('quantity'),
            'average_sale' => (clone $query)->where('payment_status', Sale::PAYMENT_STATUS_PAID)->avg('total_price'),
            'in_delivery' => (clone $query)->where('delivery_status', Sale::DELIVERY_STATUS_IN_PROGRESS)->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Экспорт продаж
     */
    public function export(Request $request): JsonResponse
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
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        // Формируем данные для экспорта
        $exportData = $sales->map(function ($sale) {
            return [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'customer_name' => $sale->customer_name,
                'customer_phone' => $sale->customer_phone,
                'customer_email' => $sale->customer_email,
                'product_name' => $sale->product->name ?? '',
                'warehouse' => $sale->warehouse->name ?? '',
                'quantity' => $sale->quantity,
                'unit_price' => $sale->unit_price,
                'total_price' => $sale->total_price,
                'payment_status' => $sale->payment_status,
                'delivery_status' => $sale->delivery_status,
                'payment_method' => $sale->payment_method,
                'sale_date' => $sale->sale_date,
                'delivery_date' => $sale->delivery_date,
                'created_by' => $sale->user->name ?? '',
                'created_at' => $sale->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $exportData,
            'total' => $exportData->count(),
        ]);
    }
}
