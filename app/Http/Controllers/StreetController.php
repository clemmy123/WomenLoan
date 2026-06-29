<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Street;
use Illuminate\Http\Request;

class StreetController extends Controller
{
    /**
     * Display a listing of all streets.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json(Street::all());
    }

    /**
     * Display streets by ward ID.
     *
     * @param int $wardId
     * @return \Illuminate\Http\JsonResponse
     */
    public function byWard(int $wardId)
    {
        $streets = Street::where('ward_id', $wardId)->get();
        return response()->json($streets);
    }

    /**
     * Alias for byWard to support routes expecting getStreets.
     *
     * @param int $wardId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStreets(int $wardId)
    {
        return $this->byWard($wardId);
    }

    /**
     * Display a specific street by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $street = Street::findOrFail($id);
        return response()->json($street);
    }

    /**
     * Store a newly created street.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'ward_id' => 'required|exists:wards,id',
        ]);

        $street = Street::create($validated);

        return response()->json($street, 201);
    }

    /**
     * Update an existing street.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        $street = Street::findOrFail($id);

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'ward_id' => 'required|exists:wards,id',
        ]);

        $street->update($validated);

        return response()->json($street);
    }

    /**
     * Delete a street.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $street = Street::findOrFail($id);
        $street->delete();

        return response()->json(['message' => 'Street deleted successfully']);
    }
}
