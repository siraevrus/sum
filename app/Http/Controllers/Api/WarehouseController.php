<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    /**
     * Получить список складов
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Warehouse::query();

        // Ограничение по складу для не-админа: видит только свой склад
        if ($user && method_exists($user, 'isAdmin') && ! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Фильтрация по компании
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Фильтрация по статусу активности
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Поиск по названию или адресу
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        $warehouses = $query->with(['company', 'employees'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $warehouses->items(),
            'pagination' => [
                'current_page' => $warehouses->currentPage(),
                'last_page' => $warehouses->lastPage(),
                'per_page' => $warehouses->perPage(),
                'total' => $warehouses->total(),
            ],
        ]);
    }

    /**
     * Получить конкретный склад
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        // Ограничение доступа к конкретному складу
        $user = Auth::user();
        if ($user && method_exists($user, 'isAdmin') && ! $user->isAdmin()) {
            if ((int) $user->warehouse_id !== (int) $warehouse->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Склад не найден',
                ], 404);
            }
        }

        $warehouse->load(['company', 'employees', 'products']);

        return response()->json([
            'success' => true,
            'data' => $warehouse,
        ]);
    }

    /**
     * Создать новый склад
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'company_id' => 'required|exists:companies,id',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $warehouse = Warehouse::create($validated);
        $warehouse->load(['company']);

        return response()->json([
            'success' => true,
            'message' => 'Склад успешно создан',
            'data' => $warehouse,
        ], 201);
    }

    /**
     * Обновить склад
     */
    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:500',
            'company_id' => 'sometimes|exists:companies,id',
            'is_active' => 'sometimes|boolean',
        ]);

        // Ограничение доступа
        $user = Auth::user();
        if ($user && method_exists($user, 'isAdmin') && ! $user->isAdmin()) {
            if ((int) $user->warehouse_id !== (int) $warehouse->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доступ запрещен',
                ], 403);
            }
        }

        $warehouse->update($validated);
        $warehouse->load(['company']);

        return response()->json([
            'success' => true,
            'message' => 'Склад успешно обновлен',
            'data' => $warehouse,
        ]);
    }

    /**
     * Удалить склад
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        // Ограничение доступа
        $user = Auth::user();
        if ($user && method_exists($user, 'isAdmin') && ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен',
            ], 403);
        }

        // Проверяем, есть ли товары на складе
        if ($warehouse->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить склад, на котором есть товары',
            ], 400);
        }

        // Проверяем, есть ли сотрудники на складе
        if ($warehouse->employees()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить склад, на котором работают сотрудники',
            ], 400);
        }

        $warehouse->delete();

        return response()->json([
            'success' => true,
            'message' => 'Склад успешно удален',
        ]);
    }

    /**
     * Активировать склад
     */
    public function activate(Warehouse $warehouse): JsonResponse
    {
        // Ограничение доступа
        $user = Auth::user();
        if ($user && method_exists($user, 'isAdmin') && ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен',
            ], 403);
        }

        $warehouse->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Склад активирован',
            'data' => $warehouse->load(['company']),
        ]);
    }

    /**
     * Деактивировать склад
     */
    public function deactivate(Warehouse $warehouse): JsonResponse
    {
        // Ограничение доступа
        $user = Auth::user();
        if ($user && method_exists($user, 'isAdmin') && ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен',
            ], 403);
        }

        $warehouse->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Склад деактивирован',
            'data' => $warehouse->load(['company']),
        ]);
    }

    /**
     * Получить статистику склада
     */
    public function stats(Warehouse $warehouse): JsonResponse
    {
        $stats = [
            'total_products' => $warehouse->products()->count(),
            'active_products' => $warehouse->products()->where('is_active', true)->count(),
            'total_employees' => $warehouse->employees()->count(),
            'total_volume' => $warehouse->products()->sum('calculated_volume'),
            'total_quantity' => $warehouse->products()->sum('quantity'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Получить товары склада
     */
    public function products(Request $request, Warehouse $warehouse): JsonResponse
    {
        // Ограничение доступа
        $user = Auth::user();
        if ($user && method_exists($user, 'isAdmin') && ! $user->isAdmin()) {
            if ((int) $user->warehouse_id !== (int) $warehouse->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доступ запрещен',
                ], 403);
            }
        }

        $query = $warehouse->products();

        // Фильтрация по активности
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Фильтрация по шаблону товара
        if ($request->has('product_template_id')) {
            $query->where('product_template_id', $request->product_template_id);
        }

        // Поиск по названию
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Сортировка
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        $products = $query->with(['productTemplate'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Получить сотрудников склада
     */
    public function employees(Request $request, Warehouse $warehouse): JsonResponse
    {
        // Ограничение доступа
        $user = Auth::user();
        if ($user && method_exists($user, 'isAdmin') && ! $user->isAdmin()) {
            if ((int) $user->warehouse_id !== (int) $warehouse->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доступ запрещен',
                ], 403);
            }
        }

        $query = $warehouse->employees();

        // Фильтрация по роли
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Фильтрация по статусу блокировки
        if ($request->has('is_blocked')) {
            $query->where('is_blocked', $request->boolean('is_blocked'));
        }

        // Поиск по имени или email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        $employees = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $employees->items(),
            'pagination' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
            ],
        ]);
    }

    /**
     * Получить статистику всех складов
     */
    public function statsAll(): JsonResponse
    {
        $user = Auth::user();
        $query = Warehouse::query();

        // Ограничение по складу
        if ($user && method_exists($user, 'isAdmin') && ! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $stats = [
            'total' => $query->count(),
            'active' => $query->where('is_active', true)->count(),
            'inactive' => $query->where('is_active', false)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
