<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Список компаний
     */
    public function index(Request $request): JsonResponse
    {
        $query = Company::query();

        // Фильтр по активности (is_active=true => не архивные)
        if ($request->has('is_active')) {
            $isActive = filter_var($request->get('is_active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_archived', ! $isActive);
        }

        // Поиск по названию / email / ИНН
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('inn', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) $request->get('per_page', 15);
        $companies = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $companies->items(),
            'pagination' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ],
        ]);
    }

    /**
     * Получить конкретную компанию
     */
    public function show(Company $company): JsonResponse
    {
        // Проверяем, что компания не архивирована
        if ($company->is_archived) {
            return response()->json([
                'success' => false,
                'message' => 'Компания не найдена',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $company,
        ]);
    }

    /**
     * Создать новую компанию
     */
    public function store(Request $request): JsonResponse
    {
        // Валидация данных
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_address' => 'nullable|string|max:500',
            'postal_address' => 'nullable|string|max:500',
            'phone_fax' => 'nullable|string|max:100',
            'general_director' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'inn' => 'nullable|string|max:12|unique:companies,inn',
            'kpp' => 'nullable|string|max:9',
            'ogrn' => 'nullable|string|max:15',
            'bank' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:20',
            'correspondent_account' => 'nullable|string|max:20',
            'bik' => 'nullable|string|max:9',
            'employees_count' => 'nullable|integer|min:0',
            'warehouses_count' => 'nullable|integer|min:0',
        ]);

        // Явные значения по умолчанию, чтобы не передавать NULL в not-null колонки
        $validated['employees_count'] = $validated['employees_count'] ?? 0;
        $validated['warehouses_count'] = $validated['warehouses_count'] ?? 0;

        try {
            // Создаем компанию
            $company = Company::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Компания успешно создана',
                'data' => $company,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании компании',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить компанию
     */
    public function update(Request $request, Company $company): JsonResponse
    {
        // Проверяем, что компания не архивирована
        if ($company->is_archived) {
            return response()->json([
                'success' => false,
                'message' => 'Компания архивирована',
            ], 404);
        }

        // Валидация данных
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'legal_address' => 'nullable|string|max:500',
            'postal_address' => 'nullable|string|max:500',
            'phone_fax' => 'nullable|string|max:100',
            'general_director' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'inn' => 'nullable|string|max:12|unique:companies,inn,'.$company->id,
            'kpp' => 'nullable|string|max:9',
            'ogrn' => 'nullable|string|max:15',
            'bank' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:20',
            'correspondent_account' => 'nullable|string|max:20',
            'bik' => 'nullable|string|max:9',
            'employees_count' => 'nullable|integer|min:0',
            'warehouses_count' => 'nullable|integer|min:0',
        ]);

        // Не допускаем запись NULL в счетчики
        if (array_key_exists('employees_count', $validated) && $validated['employees_count'] === null) {
            $validated['employees_count'] = 0;
        }
        if (array_key_exists('warehouses_count', $validated) && $validated['warehouses_count'] === null) {
            $validated['warehouses_count'] = 0;
        }

        try {
            // Обновляем компанию
            $company->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Компания успешно обновлена',
                'data' => $company->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении компании',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить компанию
     */
    public function destroy(Company $company): JsonResponse
    {
        // Проверяем, что компания не архивирована
        if ($company->is_archived) {
            return response()->json([
                'success' => false,
                'message' => 'Компания не найдена',
            ], 404);
        }

        // Блокируем удаление, если есть связанные склады или сотрудники
        if ($company->warehouses()->exists() || $company->employees()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить компанию с привязанными складами или сотрудниками. Архивируйте или удалите связанные записи.',
            ], 400);
        }

        try {
            // Архивируем компанию (soft delete)
            $company->archive();

            return response()->json([
                'success' => true,
                'message' => 'Компания успешно удалена',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении компании',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить склады компании
     */
    public function warehouses(Request $request, Company $company): JsonResponse
    {
        // Проверяем, что компания не архивирована
        if ($company->is_archived) {
            return response()->json([
                'success' => false,
                'message' => 'Компания архивирована',
            ], 404);
        }

        $query = Warehouse::where('company_id', $company->id);

        // Фильтр по активности
        if ($request->has('is_active')) {
            $isActive = filter_var($request->get('is_active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        // Поиск по названию
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Сортировка
        $sortBy = $request->get('sort', 'name');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) $request->get('per_page', 15);
        $warehouses = $query->paginate($perPage);

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
}
