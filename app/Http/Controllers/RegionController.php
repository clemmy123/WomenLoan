<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * Display a listing of regions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Rudisha regions zote kama JSON
        return response()->json(Region::all());
    }

    /**
     * Display a specific region by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $region = Region::findOrFail($id);
        return response()->json($region);
    }

    /**
     * Store a newly created region.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
        ]);

        $region = Region::create($validated);

        return response()->json($region, 201);
    }

    /**
     * Update an existing region.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        $region = Region::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
        ]);

        $region->update($validated);

        return response()->json($region);
    }

    /**
     * Delete a region.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $region = Region::findOrFail($id);
        $region->delete();

        return response()->json(['message' => 'Region deleted successfully']);
    }

    /**
     * Get districts by region ID.
     *
     * @param int $regionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDistricts(int $regionId)
    {
        $region = Region::findOrFail($regionId);
        return response()->json($region->districts);
    }

    /**
     * Get councils by district ID.
     *
     * @param int $districtId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCouncils(int $districtId)
    {
        return response()->json(\App\Models\District::findOrFail($districtId)->councils);
    }

    /**
     * Get wards by council ID.
     *
     * @param int $councilId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWards(int $councilId)
    {
        return response()->json(\App\Models\Council::findOrFail($councilId)->wards);
    }

    /**
     * Get streets by ward ID.
     *
     * @param int $wardId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStreets(int $wardId)
    {
        return response()->json(\App\Models\Ward::findOrFail($wardId)->streets);
    }
}
