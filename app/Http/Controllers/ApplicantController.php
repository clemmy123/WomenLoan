<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Region;   
use App\Models\District; 
use App\Models\Council;  
use App\Models\Ward;     
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicantController extends Controller
{
    /**
     * Display a listing of the applicants.
     */
    public function index(Request $request)
    {
        $applicants = Applicant::query()
            ->select(['id', 'first_name', 'middle_name', 'last_name', 'full_name', 'nin', 'dob', 'phone', 'email', 'sex', 'marital_status', 'location_id'])
            ->withCount(['loans', 'groups'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('full_name', 'like', "%{$search}%")
                      ->orWhere('nin', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('applicants.index', compact('applicants'));
    }

    /**
     * Show the form for creating a new applicant.
     */
    public function create()
    {
        $regions = Region::orderBy('name', 'asc')->get();
        return view('applicants.create', compact('regions'));
    }

    /**
     * Store a newly created applicant in storage.
     */
    public function store(Request $request)
    {
        if ($request->has('phone')) {
            $request->merge(['phone' => str_replace([' ', '+'], '', $request->phone)]);
        }

        $validated = $request->validate([
            'first_name'     => ['required', 'string', 'max:255', 'min:2'],
            'middle_name'    => ['nullable', 'string', 'max:255'],
            'last_name'      => ['required', 'string', 'max:255', 'min:2'],
            'nin'            => ['required', 'numeric', 'digits:20', Rule::unique('applicants', 'nin')],
            'dob'            => ['required', 'date', 'before:today'],
            'email'          => ['required', 'email', 'max:255', Rule::unique('applicants', 'email')],
            'phone'          => ['required', 'string', 'regex:/^(?:255|0)[67][1-9]\d{7}$/', Rule::unique('applicants', 'phone')],
            'sex'            => ['required', 'string', 'in:Male,Female'],
            'marital_status' => ['nullable', 'string', 'max:20'],
            'nationality'    => ['nullable', 'string', 'max:255'],
            'location_id'    => ['required', 'integer', 'exists:streets,id'], 
        ]);

        $middle = $validated['middle_name'] ? ' ' . $validated['middle_name'] : '';
        $validated['full_name'] = $validated['first_name'] . $middle . ' ' . $validated['last_name'];
        $validated['user_id'] = auth()->id() ?? 1;
        $validated['nationality'] = $request->input('nationality', 'Tanzanian');

        $applicant = Applicant::create($validated);

        return redirect()->route('applicants.show', $applicant)->with('success', 'Applicant registered successfully.');
    }

    /**
     * Display the specified applicant profile.
     */
    public function show(Applicant $applicant)
    {
        $applicant->load(['groups', 'loans']);
        return view('applicants.show', compact('applicant'));
    }

    /**
     * Show the form for editing the specified applicant.
     */
    public function edit(Applicant $applicant)
    {
        // Load the full location hierarchy for the dropdowns
        $applicant->load('location.ward.council.district.region');
        
        $regions = Region::orderBy('name', 'asc')->get();
        $location = $applicant->location;
        $ward = $location?->ward;
        $council = $ward?->council;
        $district = $council?->district;
        $region = $district?->region;

        return view('applicants.edit', compact(
            'applicant', 'regions', 'region', 'district', 'council', 'ward', 'location'
        ));
    }

    /**
     * Update the specified applicant in storage.
     */
    public function update(Request $request, Applicant $applicant)
    {
        if ($request->has('phone')) {
            $request->merge(['phone' => str_replace([' ', '+'], '', $request->phone)]);
        }

        $validated = $request->validate([
            'first_name'     => ['required', 'string', 'max:255', 'min:2'],
            'middle_name'    => ['nullable', 'string', 'max:255'],
            'last_name'      => ['required', 'string', 'max:255', 'min:2'],
            'nin'            => ['required', 'numeric', 'digits:20', Rule::unique('applicants', 'nin')->ignore($applicant->id)],
            'dob'            => ['required', 'date', 'before:today'],
            'email'          => ['required', 'email', 'max:255', Rule::unique('applicants', 'email')->ignore($applicant->id)],
            'phone'          => ['required', 'string', 'regex:/^(?:255|0)[67][1-9]\d{7}$/', Rule::unique('applicants', 'phone')->ignore($applicant->id)],
            'sex'            => ['required', 'string', 'in:Male,Female'],
            'marital_status' => ['nullable', 'string', 'max:20'],
            'nationality'    => ['nullable', 'string', 'max:255'],
            'location_id'    => ['required', 'integer', 'exists:streets,id'], 
        ]);

        $middle = $validated['middle_name'] ? ' ' . $validated['middle_name'] : '';
        $validated['full_name'] = $validated['first_name'] . $middle . ' ' . $validated['last_name'];

        $applicant->update($validated);

        return redirect()->route('applicants.show', $applicant)->with('success', 'Applicant updated successfully.');
    }

    /**
     * Remove the specified applicant from storage.
     */
    public function destroy(Applicant $applicant)
    {
        $applicant->delete();
        return redirect()->route('applicants.index')->with('success', 'The applicant has been deleted successfully.');
    }

    /**
     * ======================================================================
     * CASCADING LOCATION SUBSYSTEM ENDPOINTS
     * ======================================================================
     */
    public function getDistricts(Region $region) { return response()->json($region->districts()->orderBy('name', 'asc')->get()); }
    public function getCouncils(District $district) { return response()->json($district->councils()->orderBy('name', 'asc')->get()); }
    public function getWards(Council $council) { return response()->json($council->wards()->orderBy('name', 'asc')->get()); }
    public function getStreets(Ward $ward) { return response()->json($ward->streets()->orderBy('name', 'asc')->get()); }
}