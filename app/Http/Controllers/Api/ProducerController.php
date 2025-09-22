<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProducerController extends Controller
{
    public function index(): JsonResponse
    {
        $producers = Producer::withCount('products')->get();

        return response()->json($producers);
    }

    public function store(Request $request): JsonResponse
    {
        // Validate and create a new producer
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
        ]);

        $producer = Producer::create($validated);

        return response()->json($producer, 201);
    }

    public function show(Producer $producer): JsonResponse
    {
        $producer->loadCount('products');

        return response()->json($producer);
    }

    public function update(Request $request, Producer $producer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'region' => 'sometimes|nullable|string|max:255',
        ]);

        $producer->update($validated);

        return response()->json($producer);
    }

    public function destroy(Producer $producer): JsonResponse
    {
        $producer->delete();

        return response()->json(null, 204);
    }
}
