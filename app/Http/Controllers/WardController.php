<?php

namespace App\Http\Controllers;

use App\Models\Council;
use App\Models\Ward;
use App\Services\GeoHierarchyService;
use Illuminate\Http\Request;

class WardController extends Controller
{
    public function __construct(private GeoHierarchyService $geo) {}
    /**
     * Display a listing of all wards.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json(Ward::all());
    }

    /**
     * Display wards by council ID.
     *
     * @param int $councilId
     * @return \Illuminate\Http\JsonResponse
     */
    public function byCouncil(int $councilId)
    {
        $wards = Ward::where('council_id', $councilId)->get();
        return response()->json($wards);
    }

    /**
     * Display wards by council ID for cascading dropdowns.
     *
     * @param int $councilId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWards(int $councilId)
    {
        return response()->json($this->geo->wardsForUser(Council::findOrFail($councilId)));
    }

    /**
     * Display wards by district ID.
     *
     * @param int $districtId
     * @return \Illuminate\Http\JsonResponse
     */
    public function byDistrict(int $districtId)
    {
        $wards = Ward::where('district_id', $districtId)->get();
        return response()->json($wards);
    }

    /**
     * Display a specific ward by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $ward = Ward::findOrFail($id);
        return response()->json($ward);
    }

    /**
     * Store a newly created ward.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'code'       => 'nullable|string|max:50',
            'council_id' => 'required|exists:councils,id',
            'district_id'=> 'required|exists:districts,id',
        ]);

        $ward = Ward::create($validated);

        return response()->json($ward, 201);
    }

    /**
     * Update an existing ward.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        $ward = Ward::findOrFail($id);

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'code'       => 'nullable|string|max:50',
            'council_id' => 'required|exists:councils,id',
            'district_id'=> 'required|exists:districts,id',
        ]);

        $ward->update($validated);

        return response()->json($ward);
    }

    /**
     * Delete a ward.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $ward = Ward::findOrFail($id);
        $ward->delete();

        return response()->json(['message' => 'Ward deleted successfully']);
    }
}
