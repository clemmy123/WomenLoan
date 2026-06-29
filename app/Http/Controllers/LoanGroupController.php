<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\LoanGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class LoanGroupController extends Controller
{
    /**
     * Display a listing of loan groups.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $loanGroups = LoanGroup::query()
            ->withCount(['applicants', 'loans'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('registration_number', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return view('loan_groups.index', compact('loanGroups'));
    }

    /**
     * Show form for creating a group with Regional Filtering.
     */
    public function create(Request $request)
    {
        $regionId = $request->query('region_id');

        // Vuta applicants wa mkoa husika tu ambao hawana kundi
        $applicants = Applicant::query()
            ->when($regionId, function ($query) use ($regionId) {
                $query->whereHas('location', function ($q) use ($regionId) {
                    $q->where('region_id', $regionId);
                });
            })
            ->whereDoesntHave('groups')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'nin']);

        return view('loan_groups.create', compact('applicants', 'regionId'));
    }

    /**
     * Store new group with strict uniqueness validation.
     */
    public function store(Request $request)
    {
        // 1. Clean Phone
        if ($request->has('phone')) {
            $request->merge(['phone' => str_replace([' ', '+'], '', $request->phone)]);
        }

        // 2. Validate with strict Regional & NIN Membership logic
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255', Rule::unique('loan_groups', 'name')],
            'region_id'           => ['required', 'exists:regions,id'],
            'registration_number' => ['nullable', 'unique:loan_groups,registration_number'],
            'phone'               => ['nullable', 'regex:/^(?:255|0)[67][1-9]\d{7}$/'],
            'applicants'          => ['nullable', 'array'],
            'applicants.*'        => [
                'integer', 
                'exists:applicants,id',
                function ($attribute, $value, $fail) {
                    // Check if applicant already exists in ANY group
                    $exists = DB::table('applicant_loan_group')->where('applicant_id', $value)->exists();
                    if ($exists) {
                        $name = Applicant::find($value)->full_name ?? 'Applicant';
                        $fail("{$name} tayari ameshajisajili kwenye kundi lingine.");
                    }
                }
            ],
        ]);

        // 3. Create Group
        $loanGroup = LoanGroup::create($request->only(['name', 'registration_number', 'phone', 'email', 'region_id']));

        // 4. Sync Members
        if (!empty($validated['applicants'])) {
            $loanGroup->applicants()->sync($validated['applicants']);
        }

        return redirect()->route('loan-groups.show', $loanGroup)
            ->with('success', 'Loan group created successfully.');
    }

    /**
     * Display group details.
     */
    public function show(LoanGroup $loanGroup)
    {
        $loanGroup->load(['applicants', 'loans.payments']);
        return view('loan_groups.show', compact('loanGroup'));
    }

    /**
     * Update existing group.
     */
    public function update(Request $request, LoanGroup $loanGroup)
    {
        $request->merge(['phone' => str_replace([' ', '+'], '', $request->phone ?? '')]);

        $validated = $request->validate([
            'name' => ['required', Rule::unique('loan_groups', 'name')->ignore($loanGroup->id)],
            'applicants' => ['nullable', 'array'],
            'applicants.*' => ['integer', 'exists:applicants,id'],
        ]);

        $loanGroup->update($request->only(['name', 'phone', 'email', 'registration_number']));

        if ($request->has('applicants')) {
            $loanGroup->applicants()->sync($validated['applicants']);
        }

        return redirect()->route('loan-groups.show', $loanGroup)
            ->with('success', 'Group updated successfully.');
    }

    /**
     * Destroy group.
     */
    public function destroy(LoanGroup $loanGroup)
    {
        $loanGroup->applicants()->detach();
        $loanGroup->delete();
        return redirect()->route('loan-groups.index')->with('success', 'Group deleted.');
    }
}