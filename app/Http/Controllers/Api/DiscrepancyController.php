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
    public function show(string $id)
    {
        return Discrepancy::with(['productInTransit', 'user'])->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
