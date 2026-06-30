<?php

namespace App\Http\Controllers;

use App\Http\Requests\Applicant\StoreApplicantRequest;
use App\Http\Requests\Applicant\UpdateApplicantRequest;
use App\Models\Applicant;
use App\Models\Council;
use App\Models\District;
use App\Models\LoanGroup;
use App\Models\Region;
use App\Models\Ward;
use App\Services\ApplicantService;
use App\Services\GeoHierarchyService;
use Illuminate\Http\Request;

class ApplicantController extends Controller
{
    public function __construct(
        private ApplicantService $applicants,
        private GeoHierarchyService $geo,
    ) {}

    public function index(Request $request)
    {
        $applicants = $this->applicants->paginated($request->input('search'));

        return view('applicants.index', compact('applicants'));
    }

    public function create()
    {
        $regions = $this->geo->regions();

        return view('applicants.create', compact('regions'));
    }

    public function store(StoreApplicantRequest $request)
    {
        $applicant = $this->applicants->create($request->validated());

        return redirect()->route('applicants.show', $applicant)
            ->with('success', __('messages.applicant_created'));
    }

    public function show(Applicant $applicant)
    {
        $applicant->load(['groups', 'loans', 'user']);
        $groups = LoanGroup::query()->orderBy('name')->get(['id', 'name']);

        return view('applicants.show', array_merge(
            $this->applicants->locationContext($applicant),
            compact('groups')
        ));
    }

    public function edit(Applicant $applicant)
    {
        return view('applicants.edit', $this->applicants->locationContext($applicant));
    }

    public function update(UpdateApplicantRequest $request, Applicant $applicant)
    {
        $this->applicants->update($applicant, $request->validated());

        return redirect()->route('applicants.show', $applicant)
            ->with('success', __('messages.applicant_updated'));
    }

    public function destroy(Applicant $applicant)
    {
        $applicant->delete();

        return redirect()->route('applicants.index')
            ->with('success', __('messages.applicant_deleted'));
    }

    public function attachGroup(Applicant $applicant, Request $request)
    {
        $request->validate(['group_id' => 'required|string']);

        $group = LoanGroup::findByHashidOrFail($request->group_id);

        try {
            $this->applicants->attachToGroup($applicant, $group);
        } catch (\RuntimeException) {
            return back()->withErrors(['error' => __('messages.applicant_already_in_group', ['name' => $applicant->display_name])]);
        }

        return back()->with('success', __('messages.applicant_group_attached'));
    }

    public function detachGroup(Applicant $applicant, LoanGroup $group)
    {
        $this->applicants->detachFromGroup($applicant, $group);

        return back()->with('success', __('messages.applicant_group_detached'));
    }

    public function getDistricts(Region $region)
    {
        return response()->json($this->geo->districtsFor($region));
    }

    public function getCouncils(District $district)
    {
        return response()->json($this->geo->councilsFor($district));
    }

    public function getWards(Council $council)
    {
        return response()->json($this->geo->wardsFor($council));
    }

    public function getStreets(Ward $ward)
    {
        return response()->json($this->geo->streetsFor($ward));
    }
}
