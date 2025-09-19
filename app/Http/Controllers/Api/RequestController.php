<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    /**
     * Получить список запросов
     */
    public function index(HttpRequest $request): JsonResponse
    {
        $user = Auth::user();
        $query = Request::query();

        // Ограничение по складу для не-админа
        if ($user && method_exists($user, 'isAdmin') && ! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }


        // Фильтрация по пользователю
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Фильтрация по складу
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Фильтрация по шаблону товара
        if ($request->has('product_template_id')) {
            $query->where('product_template_id', $request->product_template_id);
        }

        // Сортировка
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        $requests = $query->with(['user', 'warehouse', 'productTemplate'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * Получить конкретный запрос
     */
    public function show(Request $request): JsonResponse
    {
        $request->load(['user', 'warehouse', 'productTemplate']);

        return response()->json([
            'success' => true,
            'data' => $request,
        ]);
    }

    /**
     * Создать новый запрос
     */
    public function store(HttpRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_template_id' => 'required|exists:product_templates,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:1',
            'status' => 'sometimes|in:pending,approved',
        ]);

        $validated['user_id'] = Auth::id();

        // Если не админ — принудительно устанавливаем склад пользователя
        $currentUser = Auth::user();
        if ($currentUser && method_exists($currentUser, 'isAdmin') && ! $currentUser->isAdmin()) {
            if (! $currentUser->warehouse_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не привязан к складу',
                ], 403);
            }
            $validated['warehouse_id'] = $currentUser->warehouse_id;
        }
        $validated['status'] = $validated['status'] ?? 'pending';

        $requestModel = Request::create($validated);
        $requestModel->load(['user', 'warehouse', 'productTemplate']);

        return response()->json([
            'success' => true,
            'message' => 'Запрос успешно создан',
            'data' => $requestModel,
        ], 201);
    }

    /**
     * Обновить запрос
     */
    public function update(HttpRequest $httpRequest, Request $request): JsonResponse
    {
        $validated = $httpRequest->validate([
            'warehouse_id' => 'sometimes|exists:warehouses,id',
            'product_template_id' => 'sometimes|exists:product_templates,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'quantity' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:pending,approved',
            'admin_notes' => 'sometimes|string',
        ]);

        $request->update($validated);
        $request->load(['user', 'warehouse', 'productTemplate']);

        return response()->json([
            'success' => true,
            'message' => 'Запрос успешно обновлен',
            'data' => $request,
        ]);
    }

    /**
     * Удалить запрос
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->delete();

        return response()->json([
            'success' => true,
            'message' => 'Запрос успешно удален',
        ]);
    }

    /**
     * Одобрить запрос
     */
    public function approve(Request $request): JsonResponse
    {
        $request->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Запрос одобрен',
            'data' => $request->load(['user', 'warehouse', 'productTemplate']),
        ]);
    }

    /**
     * Получить статистику запросов
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();
        $query = Request::query();

        // Фильтрация по компании пользователя
        if ($user->company_id) {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $stats = [
            'total' => $query->count(),
            'pending' => $query->where('status', 'pending')->count(),
            'approved' => $query->where('status', 'approved')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
