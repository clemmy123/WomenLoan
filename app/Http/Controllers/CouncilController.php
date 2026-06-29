<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Council;
use Illuminate\Http\Request;

class CouncilController extends Controller
{
    /**
     * Display a listing of all councils.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json(Council::all());
    }

    /**
     * Display councils by district ID.
     *
     * @param int $districtId
     * @return \Illuminate\Http\JsonResponse
     */
    public function byDistrict(int $districtId)
    {
        $councils = Council::where('district_id', $districtId)->get();
        return response()->json($councils);
    }

    /**
     * Get councils by district ID (for API routes).
     *
     * @param int $districtId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCouncils(int $districtId)
    {
        $councils = Council::where('district_id', $districtId)->get();
        return response()->json($councils);
    }

    /**
     * Display a specific council by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $council = Council::findOrFail($id);
        return response()->json($council);
    }

    /**
     * Store a newly created council.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'district_id' => 'required|exists:districts,id',
        ]);

        $council = Council::create($validated);

        return response()->json($council, 201);
    }

    /**
     * Update an existing council.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        $council = Council::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'district_id' => 'required|exists:districts,id',
        ]);

        $council->update($validated);

        return response()->json($council);
    }

    /**
     * Delete a council.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $council = Council::findOrFail($id);
        $council->delete();

        return response()->json(['message' => 'Council deleted successfully']);
    }
}
