<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceiptController extends Controller
{
    /**
     * Создать товар(ы) в пути
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;

        $validated = $request->validate([
            'warehouse_id' => $isAdmin ? ['required', 'integer', 'exists:warehouses,id'] : ['nullable'],
            'shipping_location' => ['nullable', 'string', 'max:255'],
            'shipping_date' => ['nullable', 'date'],
            'transport_number' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'expected_arrival_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'document_path' => ['nullable', 'array'],
            'products' => ['nullable', 'array', 'min:1'],
            'products.*.product_template_id' => ['required_without:product_template_id', 'integer', 'exists:product_templates,id'],
            'products.*.quantity' => ['nullable', 'integer', 'min:1'],
            'products.*.producer' => ['nullable', 'string', 'max:255'],
            'products.*.description' => ['nullable', 'string', 'max:1000'],
            'products.*.name' => ['nullable', 'string', 'max:255'],
            'products.*.attributes' => ['nullable', 'array'],

            // Плоский вариант (без массива products)
            'product_template_id' => ['required_without:products', 'integer', 'exists:product_templates,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'producer' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'name' => ['nullable', 'string', 'max:255'],
            'attributes' => ['nullable', 'array'],
        ]);

        $warehouseId = $isAdmin ? ($validated['warehouse_id'] ?? null) : ($user?->warehouse_id);
        if (! $warehouseId) {
            return response()->json([
                'success' => false,
                'message' => 'Склад не указан',
            ], 422);
        }

        $common = [
            'warehouse_id' => $warehouseId,
            'shipping_location' => $validated['shipping_location'] ?? null,
            'shipping_date' => $validated['shipping_date'] ?? now()->toDateString(),
            'transport_number' => $validated['transport_number'] ?? null,
            'tracking_number' => $validated['tracking_number'] ?? null,
            'expected_arrival_date' => $validated['expected_arrival_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'document_path' => $validated['document_path'] ?? [],
            'status' => Product::STATUS_IN_TRANSIT,
            'is_active' => true,
            'created_by' => $user?->id,
        ];

        $created = [];

        DB::beginTransaction();
        try {
            $items = [];
            if (! empty($validated['products']) && is_array($validated['products'])) {
                $items = $validated['products'];
            } else {
                $items[] = [
                    'product_template_id' => $validated['product_template_id'],
                    'quantity' => $validated['quantity'] ?? 1,
                    'producer' => $validated['producer'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'name' => $validated['name'] ?? null,
                    'attributes' => $validated['attributes'] ?? [],
                ];
            }

            foreach ($items as $item) {
                $productData = array_merge($common, [
                    'product_template_id' => $item['product_template_id'],
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'producer' => $item['producer'] ?? null,
                    'description' => $item['description'] ?? null,
                    'name' => $item['name'] ?? null,
                    'attributes' => $item['attributes'] ?? [],
                ]);

                // Генерация имени и объёма по формуле шаблона
                $template = ProductTemplate::find($productData['product_template_id']);
                if ($template) {
                    // Имя
                    if (empty($productData['name'])) {
                        $nameParts = [];
                        foreach ($template->attributes as $templateAttribute) {
                            $key = $templateAttribute->variable;
                            if (isset($productData['attributes'][$key]) && $productData['attributes'][$key] !== null) {
                                $nameParts[] = $productData['attributes'][$key];
                            }
                        }
                        if (! empty($nameParts)) {
                            $productData['name'] = ($template->name ?? 'Товар').': '.implode(', ', $nameParts);
                        }
                    }

                    // Объём
                    if ($template->formula && ! empty($productData['attributes'])) {
                        $attrsForFormula = [];
                        foreach ($productData['attributes'] as $k => $v) {
                            if (is_numeric($v)) {
                                $attrsForFormula[$k] = (float) $v;
                            }
                        }
                        $attrsForFormula['quantity'] = $productData['quantity'];

                        if (! empty($attrsForFormula)) {
                            $test = $template->testFormula($attrsForFormula);
                            if (is_array($test) && ($test['success'] ?? false)) {
                                $productData['calculated_volume'] = (float) $test['result'];
                            }
                        }
                    }
                }

                $created[] = Product::create($productData);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании товара(ов) в пути',
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Товар(ы) в пути успешно созданы',
            'data' => count($created) === 1 ? $created[0] : $created,
        ], 201);
    }
    /**
     * Список приемок (товары со статусом «Прибыл»)
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Product::query()
            ->where('status', Product::STATUS_IN_TRANSIT)
            ->where('is_active', true)
            ->with(['warehouse', 'template', 'creator']);

        // Ограничение по складу для не-админа
        if ($user && ! $user->isAdmin()) {
            if ($user->warehouse_id) {
                $query->where('warehouse_id', $user->warehouse_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Фильтры
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }
        if ($request->filled('shipping_location')) {
            $query->where('shipping_location', $request->input('shipping_location'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('producer', 'like', "%{$search}%")
                    ->orWhere('shipping_location', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = (int) $request->get('per_page', 15);
        $receipts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $receipts->items(),
            'pagination' => [
                'current_page' => $receipts->currentPage(),
                'last_page' => $receipts->lastPage(),
                'per_page' => $receipts->perPage(),
                'total' => $receipts->total(),
            ],
        ]);
    }

    /**
     * Просмотр приемки
     */
    public function show(Product $receipt): JsonResponse
    {
        // Доступ только к активным в пути
        if ($receipt->status !== Product::STATUS_IN_TRANSIT || ! $receipt->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Запись не найдена',
            ], 404);
        }

        // Ограничение по складу для не-админа
        $user = Auth::user();
        if ($user && ! $user->isAdmin()) {
            if ($user->warehouse_id !== $receipt->warehouse_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Запись не найдена',
                ], 404);
            }
        }

        $receipt->load(['warehouse', 'template', 'creator']);

        return response()->json([
            'success' => true,
            'data' => $receipt,
        ]);
    }

    /**
     * Принять товар (перевести в остатки)
     */
    public function receive(Product $receipt): JsonResponse
    {
        // Ограничение по складу для не-админа
        $user = Auth::user();
        if ($user && ! $user->isAdmin()) {
            if ($user->warehouse_id !== $receipt->warehouse_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Запись не найдена',
                ], 404);
            }
        }

        $receipt->markInStock();

        return response()->json([
            'success' => true,
            'message' => 'Товар принят',
            'data' => $receipt->refresh(),
        ]);
    }
}
