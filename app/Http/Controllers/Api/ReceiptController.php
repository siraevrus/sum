<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductInTransit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceiptController extends Controller
{
    /**
     * Список приемок (товары со статусом «Прибыл»)
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = ProductInTransit::query()
            ->where('status', ProductInTransit::STATUS_ARRIVED)
            ->where('is_active', true)
            ->with(['warehouse', 'template', 'creator']);

        // Ограничение по компании для не-админа
        if ($user && ! $user->isAdmin() && $user->company_id) {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        // Фильтры
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }
        if ($request->filled('shipping_location')) {
            $query->where('shipping_location', $request->input('shipping_location'));
        }
        if ($request->filled('shipment_number')) {
            $query->where('shipment_number', $request->input('shipment_number'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('producer', 'like', "%{$search}%")
                    ->orWhere('shipping_location', 'like', "%{$search}%")
                    ->orWhere('shipment_number', 'like', "%{$search}%");
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
    public function show(ProductInTransit $receipt): JsonResponse
    {
        // Доступ только к активным и прибывшим
        if ($receipt->status !== ProductInTransit::STATUS_ARRIVED || ! $receipt->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Запись не найдена',
            ], 404);
        }

        // Ограничение по компании для не-админа
        $user = Auth::user();
        if ($user && ! $user->isAdmin() && $user->company_id) {
            $receipt->load('warehouse');
            if (! $receipt->warehouse || $receipt->warehouse->company_id !== $user->company_id) {
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
    public function receive(ProductInTransit $receipt): JsonResponse
    {
        // Ограничение по компании для не-админа
        $user = Auth::user();
        if ($user && ! $user->isAdmin() && $user->company_id) {
            $receipt->load('warehouse');
            if (! $receipt->warehouse || $receipt->warehouse->company_id !== $user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Запись не найдена',
                ], 404);
            }
        }

        if (! $receipt->canBeReceived()) {
            return response()->json([
                'success' => false,
                'message' => 'Товар нельзя принять',
            ], 400);
        }

        $ok = $receipt->receive();
        if (! $ok) {
            return response()->json([
                'success' => false,
                'message' => 'Не удалось принять товар',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Товар принят',
            'data' => $receipt->refresh(),
        ]);
    }
}
