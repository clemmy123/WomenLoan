<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    /**
     * Display a listing of all districts.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json(District::all());
    }

    /**
     * Display districts by region ID.
     *
     * @param int $regionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function byRegion(int $regionId)
    {
        $districts = District::where('region_id', $regionId)->get();
        return response()->json($districts);
    }

    /**
     * Display a specific district by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $district = District::findOrFail($id);
        return response()->json($district);
    }

    /**
     * Store a newly created district.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'region_id' => 'required|exists:regions,id',
        ]);

        $district = District::create($validated);

        return response()->json($district, 201);
    }

    /**
     * Update an existing district.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        $district = District::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'region_id' => 'required|exists:regions,id',
        ]);

        $district->update($validated);

        return response()->json($district);
    }

    /**
     * Delete a district.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $district = District::findOrFail($id);
        $district->delete();

        return response()->json(['message' => 'District deleted successfully']);
    }

    /**
     * Get districts by region ID for cascading dropdown.
     *
     * @param int $regionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDistricts(int $regionId)
    {
        $districts = District::where('region_id', $regionId)->get(['id', 'name']);
        return response()->json($districts);
    }

    /**
     * Get councils by district ID for cascading dropdown.
     *
     * @param int $districtId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCouncils(int $districtId)
    {
        // Implement based on your Council model
        return response()->json([]);
    }

    /**
     * Get wards by council ID for cascading dropdown.
     *
     * @param int $councilId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWards(int $councilId)
    {
        // Implement based on your Ward model
        return response()->json([]);
    }

    /**
     * Get streets by ward ID for cascading dropdown.
     *
     * @param int $wardId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStreets(int $wardId)
    {
        // Implement based on your Street model
        return response()->json([]);
    }
}
