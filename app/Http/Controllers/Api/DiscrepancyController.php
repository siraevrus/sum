<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discrepancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscrepancyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Discrepancy::with(['productInTransit', 'user'])->orderByDesc('created_at')->paginate(20);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_in_transit_id' => 'required|exists:product_in_transit,id',
            'reason' => 'required|string|max:255',
            'old_quantity' => 'nullable|integer',
            'new_quantity' => 'nullable|integer',
            'old_color' => 'nullable|string|max:255',
            'new_color' => 'nullable|string|max:255',
            'old_size' => 'nullable|string|max:255',
            'new_size' => 'nullable|string|max:255',
            'old_weight' => 'nullable|numeric',
            'new_weight' => 'nullable|numeric',
        ]);
        $data['user_id'] = Auth::id();
        $discrepancy = Discrepancy::create($data);

        return response()->json($discrepancy->load(['productInTransit', 'user']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Discrepancy $discrepancy)
    {
        return response()->json($discrepancy->load(['productInTransit', 'user']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Discrepancy $discrepancy)
    {
        $data = $request->validate([
            'reason' => 'sometimes|string|max:255',
            'old_quantity' => 'sometimes|nullable|integer',
            'new_quantity' => 'sometimes|nullable|integer',
            'old_color' => 'sometimes|nullable|string|max:255',
            'new_color' => 'sometimes|nullable|string|max:255',
            'old_size' => 'sometimes|nullable|string|max:255',
            'new_size' => 'sometimes|nullable|string|max:255',
            'old_weight' => 'sometimes|nullable|numeric',
            'new_weight' => 'sometimes|nullable|numeric',
        ]);

        $discrepancy->update($data);

        return response()->json($discrepancy->load(['productInTransit', 'user']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Discrepancy $discrepancy)
    {
        $discrepancy->delete();

        return response()->json(null, 204);
    }
}
