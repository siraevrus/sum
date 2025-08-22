<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        // Остальные видят только пользователей своего склада
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
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

        // Поиск по ФИО, имени, email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
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
        $current = Auth::user();
        if (! $current->isAdmin() && $current->warehouse_id !== $user->warehouse_id) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен',
            ], 403);
        }

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
        // Только администратор может создавать пользователей через API
        if (! Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(UserRole::cases())],
            'company_id' => 'nullable|exists:companies,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'phone' => 'nullable|string|max:20',
            'is_blocked' => 'boolean',
        ]);

        // Собираем ФИО в name, если явно не передано
        if (empty($validated['name'])) {
            $parts = array_filter([
                $validated['last_name'] ?? null,
                $validated['first_name'] ?? null,
                $validated['middle_name'] ?? null,
            ]);
            if (! empty($parts)) {
                $validated['name'] = implode(' ', $parts);
            }
        }

        // Если username не передан — используем name (или email локально)
        if (empty($validated['username'])) {
            $validated['username'] = $validated['name'] ?? ($validated['email'] ?? null);
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
        // Только администратор может обновлять пользователей через API
        if (! Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'first_name' => 'sometimes|nullable|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'middle_name' => 'sometimes|nullable|string|max:255',
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

        // Если name не передан, но меняются части ФИО — пересобираем name
        if (! isset($validated['name']) && (
            array_key_exists('first_name', $validated) ||
            array_key_exists('last_name', $validated) ||
            array_key_exists('middle_name', $validated)
        )) {
            $firstName = $validated['first_name'] ?? $user->first_name;
            $lastName = $validated['last_name'] ?? $user->last_name;
            $middleName = $validated['middle_name'] ?? $user->middle_name;

            $parts = array_filter([$lastName, $firstName, $middleName]);
            if (! empty($parts)) {
                $validated['name'] = implode(' ', $parts);
            }
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
        // Только администратор может удалять пользователей через API
        if (! Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен',
            ], 403);
        }

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
        // Только администратор может блокировать пользователей
        if (! Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен',
            ], 403);
        }

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
        // Только администратор может разблокировать пользователей
        if (! Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен',
            ], 403);
        }

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

        // Для не-админа считаем статистику только по его складу
        if (! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
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
            'first_name' => 'sometimes|nullable|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'middle_name' => 'sometimes|nullable|string|max:255',
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'sometimes|string|max:20',
            'current_password' => 'sometimes|string',
            'new_password' => 'sometimes|string|min:8',
        ]);

        // Проверка текущего пароля при смене пароля
        if (isset($validated['new_password'])) {
            if (! isset($validated['current_password']) || ! Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Неверный текущий пароль',
                ], 400);
            }

            $validated['password'] = Hash::make($validated['new_password']);
            unset($validated['current_password'], $validated['new_password']);
        }

        // Если name не передан, но меняются части ФИО — пересобираем name
        if (! isset($validated['name']) && (
            array_key_exists('first_name', $validated) ||
            array_key_exists('last_name', $validated) ||
            array_key_exists('middle_name', $validated)
        )) {
            $firstName = $validated['first_name'] ?? $user->first_name;
            $lastName = $validated['last_name'] ?? $user->last_name;
            $middleName = $validated['middle_name'] ?? $user->middle_name;

            $parts = array_filter([$lastName, $firstName, $middleName]);
            if (! empty($parts)) {
                $validated['name'] = implode(' ', $parts);
            }
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
