<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Region;
use App\Services\GeoHierarchyService;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function __construct(private GeoHierarchyService $geo) {}
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

        return response()->json($this->geo->districtsFor($region));
    }

    public function getCouncils(int $districtId)
    {
        return response()->json($this->geo->councilsFor(District::findOrFail($districtId)));
    }

    public function getWards(int $councilId)
    {
        return response()->json($this->geo->wardsFor(\App\Models\Council::findOrFail($councilId)));
    }

    public function getStreets(int $wardId)
    {
        return response()->json($this->geo->streetsFor(\App\Models\Ward::findOrFail($wardId)));
    }
}
