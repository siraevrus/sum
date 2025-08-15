<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Warehouse;
use App\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Получить список пользователей
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = User::query();

        // Администратор видит всех пользователей
        // Остальные видят только пользователей своей компании
        if (!$user->isAdmin() && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        // Фильтрация по роли
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Фильтрация по компании
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Фильтрация по складу
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
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
        $users = $query->with(['company', 'warehouse'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Получить конкретного пользователя
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['company', 'warehouse']);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Создать нового пользователя
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(UserRole::cases())],
            'company_id' => 'nullable|exists:companies,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'phone' => 'nullable|string|max:20',
            'is_blocked' => 'boolean',
        ]);

        // Если username не передан — используем name
        if (empty($validated['username'])) {
            $validated['username'] = $validated['name'];
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_blocked'] = $validated['is_blocked'] ?? false;

        $user = User::create($validated);
        $user->load(['company', 'warehouse']);

        return response()->json([
            'success' => true,
            'message' => 'Пользователь успешно создан',
            'data' => $user,
        ], 201);
    }

    /**
     * Обновить пользователя
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'role' => ['sometimes', Rule::in(UserRole::cases())],
            'company_id' => 'sometimes|exists:companies,id',
            'warehouse_id' => 'sometimes|exists:warehouses,id',
            'phone' => 'sometimes|string|max:20',
            'is_blocked' => 'sometimes|boolean',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        $user->load(['company', 'warehouse']);

        return response()->json([
            'success' => true,
            'message' => 'Пользователь успешно обновлен',
            'data' => $user,
        ]);
    }

    /**
     * Удалить пользователя
     */
    public function destroy(User $user): JsonResponse
    {
        // Нельзя удалить самого себя
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить свой аккаунт',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Пользователь успешно удален',
        ]);
    }

    /**
     * Заблокировать пользователя
     */
    public function block(User $user): JsonResponse
    {
        $user->update(['is_blocked' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Пользователь заблокирован',
            'data' => $user->load(['company', 'warehouse']),
        ]);
    }

    /**
     * Разблокировать пользователя
     */
    public function unblock(User $user): JsonResponse
    {
        $user->update(['is_blocked' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Пользователь разблокирован',
            'data' => $user->load(['company', 'warehouse']),
        ]);
    }

    /**
     * Получить статистику пользователей
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();
        $query = User::query();

        // Фильтрация по компании пользователя
        if (!$user->isAdmin() && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        $stats = [
            'total' => $query->count(),
            'active' => $query->where('is_blocked', false)->count(),
            'blocked' => $query->where('is_blocked', true)->count(),
            'by_role' => [
                'admin' => $query->where('role', UserRole::ADMIN)->count(),
                'operator' => $query->where('role', UserRole::OPERATOR)->count(),
                'worker' => $query->where('role', UserRole::WAREHOUSE_WORKER)->count(),
                'manager' => $query->where('role', UserRole::SALES_MANAGER)->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Получить профиль текущего пользователя
     */
    public function profile(): JsonResponse
    {
        $user = Auth::user();
        $user->load(['company', 'warehouse']);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Обновить профиль текущего пользователя
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'sometimes|string|max:20',
            'current_password' => 'sometimes|string',
            'new_password' => 'sometimes|string|min:8',
        ]);

        // Проверка текущего пароля при смене пароля
        if (isset($validated['new_password'])) {
            if (!isset($validated['current_password']) || !Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Неверный текущий пароль',
                ], 400);
            }

            $validated['password'] = Hash::make($validated['new_password']);
            unset($validated['current_password'], $validated['new_password']);
        }

        $user->update($validated);
        $user->load(['company', 'warehouse']);

        return response()->json([
            'success' => true,
            'message' => 'Профиль успешно обновлен',
            'data' => $user,
        ]);
    }
} 