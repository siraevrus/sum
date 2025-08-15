<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
			$query->where('is_archived', !$isActive);
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
}


