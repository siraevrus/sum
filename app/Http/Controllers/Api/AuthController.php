<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // ВАЖНО: Публичная регистрация создает только warehouse_worker
    // Для создания пользователей с другими ролями используйте UserController::store() (только для админов)

    /**
     * Регистрация пользователя
     */
    public function register(Request $request): JsonResponse
    {
        // Публичная регистрация только в dev окружении
        if (app()->environment('production')) {
            Log::warning('Попытка регистрации в продакшн окружении', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'email' => $request->input('email'),
                'timestamp' => now(),
            ]);

            return response()->json([

                'message' => 'Регистрация отключена в продакшн окружении',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'sometimes|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->get('username', $request->name),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'warehouse_worker', // Безопасная дефолтная роль
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Пользователь успешно зарегистрирован',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // ВАЖНО: Публичная регистрация создает только warehouse_worker
    // Для создания пользователей с другими ролями используйте UserController::store() (только для админов)

    /**
     * Вход пользователя
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $request->input('login');
        $password = $request->input('password');

        // Пытаемся найти пользователя по email или username
        $user = User::where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if ($user && $user->is_blocked) {
            return response()->json([
                'message' => 'Ваш аккаунт заблокирован',
            ], 401);
        }

        if (! $user || ! Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'Неверные учетные данные',
                'errors' => ['login' => ['Invalid login or password']],
            ], 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Успешный вход',
            'user' => $user,
            'token' => $token,
        ]);
    }

    // ВАЖНО: Публичная регистрация создает только warehouse_worker
    // Для создания пользователей с другими ролями используйте UserController::store() (только для админов)

    /**
     * Выход пользователя
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Успешно вышли из системы',
        ]);
    }

    // ВАЖНО: Публичная регистрация создает только warehouse_worker
    // Для создания пользователей с другими ролями используйте UserController::store() (только для админов)

    /**
     * Получение информации о текущем пользователе
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    // ВАЖНО: Публичная регистрация создает только warehouse_worker
    // Для создания пользователей с другими ролями используйте UserController::store() (только для админов)

    /**
     * Обновление профиля пользователя
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:255|unique:users,username,'.$user->id,
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        $user->update($request->only(['name', 'username', 'email']));

        if ($request->has('password')) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return response()->json([
            'message' => 'Профиль обновлен',
            'user' => $user,
        ]);
    }
}
