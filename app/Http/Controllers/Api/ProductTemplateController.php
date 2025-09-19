<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use App\Models\ProductTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductTemplateController extends Controller
{
    /**
     * Получить список шаблонов товаров
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductTemplate::query();

        // Фильтрация по активности
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
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
        $templates = $query->with(['attributes'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $templates->items(),
            'pagination' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ],
        ]);
    }

    /**
     * Получить конкретный шаблон товара
     */
    public function show(ProductTemplate $productTemplate): JsonResponse
    {
        $productTemplate->load(['attributes', 'products']);

        return response()->json([
            'success' => true,
            'data' => $productTemplate,
        ]);
    }

    /**
     * Создать новый шаблон товара
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'formula' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $template = ProductTemplate::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Шаблон товара успешно создан',
            'data' => $template,
        ], 201);
    }

    /**
     * Обновить шаблон товара
     */
    public function update(Request $request, ProductTemplate $productTemplate): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'formula' => 'sometimes|string',
            'unit' => 'sometimes|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        $productTemplate->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Шаблон товара успешно обновлен',
            'data' => $productTemplate->load(['attributes']),
        ]);
    }

    /**
     * Удалить шаблон товара
     */
    public function destroy(ProductTemplate $productTemplate): JsonResponse
    {
        // Проверяем, есть ли товары с этим шаблоном
        if ($productTemplate->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить шаблон, который используется в товарах',
            ], 400);
        }

        $productTemplate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Шаблон товара успешно удален',
        ]);
    }

    /**
     * Активировать шаблон товара
     */
    public function activate(ProductTemplate $productTemplate): JsonResponse
    {
        $productTemplate->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Шаблон товара активирован',
            'data' => $productTemplate->load(['attributes']),
        ]);
    }

    /**
     * Деактивировать шаблон товара
     */
    public function deactivate(ProductTemplate $productTemplate): JsonResponse
    {
        $productTemplate->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Шаблон товара деактивирован',
            'data' => $productTemplate->load(['attributes']),
        ]);
    }

    /**
     * Протестировать формулу шаблона
     */
    public function testFormula(Request $request, ProductTemplate $productTemplate): JsonResponse
    {
        $validated = $request->validate([
            'values' => 'required|array',
        ]);

        $result = $productTemplate->testFormula($validated['values']);

        return response()->json([
            'success' => $result['success'],
            'data' => $result,
        ]);
    }

    /**
     * Получить характеристики шаблона
     */
    public function attributes(ProductTemplate $productTemplate): JsonResponse
    {
        $attributes = $productTemplate->attributes()->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $attributes,
        ]);
    }

    /**
     * Добавить характеристику к шаблону
     */
    public function addAttribute(Request $request, ProductTemplate $productTemplate): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'variable' => 'required|string|max:100|regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/',
            'type' => 'required|in:number,text,select',
            'value' => 'nullable|string',
            'unit' => 'nullable|string|max:50',
            'is_required' => 'boolean',
            'is_in_formula' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['product_template_id'] = $productTemplate->id;
        $validated['is_required'] = $validated['is_required'] ?? false;
        $validated['is_in_formula'] = $validated['is_in_formula'] ?? false;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $attribute = ProductAttribute::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Характеристика успешно добавлена',
            'data' => $attribute,
        ], 201);
    }

    /**
     * Обновить характеристику шаблона
     */
    public function updateAttribute(Request $request, ProductTemplate $productTemplate, ProductAttribute $attribute): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'variable' => 'sometimes|string|max:100|regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/',
            'type' => 'sometimes|in:number,text,select',
            'value' => 'sometimes|string',
            'unit' => 'sometimes|string|max:50',
            'is_required' => 'sometimes|boolean',
            'is_in_formula' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
        ]);

        $attribute->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Характеристика успешно обновлена',
            'data' => $attribute,
        ]);
    }

    /**
     * Удалить характеристику шаблона
     */
    public function deleteAttribute(ProductTemplate $productTemplate, ProductAttribute $attribute): JsonResponse
    {
        $attribute->delete();

        return response()->json([
            'success' => true,
            'message' => 'Характеристика успешно удалена',
        ]);
    }

    /**
     * Получить товары с этим шаблоном
     */
    public function products(Request $request, ProductTemplate $productTemplate): JsonResponse
    {
        $query = $productTemplate->products();

        // Фильтрация по активности
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Фильтрация по складу
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
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
        $products = $query->with(['warehouse'])->paginate($perPage);

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
     * Получить статистику шаблонов
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => ProductTemplate::count(),
            'active' => ProductTemplate::where('is_active', true)->count(),
            'inactive' => ProductTemplate::where('is_active', false)->count(),
            'with_formula' => ProductTemplate::whereNotNull('formula')->count(),
            'without_formula' => ProductTemplate::whereNull('formula')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Получить доступные единицы измерения
     */
    public function units(): JsonResponse
    {
        $units = ProductTemplate::getAvailableUnits();

        return response()->json([
            'success' => true,
            'data' => $units,
        ]);
    }
}
