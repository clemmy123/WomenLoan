<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoanGroup\StoreLoanGroupRequest;
use App\Http\Requests\LoanGroup\UpdateLoanGroupRequest;
use App\Models\LoanGroup;
use App\Services\LoanGroupService;
use Illuminate\Http\Request;

class LoanGroupController extends Controller
{
    public function __construct(private LoanGroupService $groups) {}

    public function index(Request $request)
    {
        $loanGroups = $this->groups->paginated($request->input('search'));

        return view('loan_groups.index', compact('loanGroups'));
    }

    public function create(Request $request)
    {
        $regionId = $request->query('region_id');
        $applicants = $this->groups->eligibleApplicants($regionId ? (int) $regionId : null);

        return view('loan_groups.create', compact('applicants', 'regionId'));
    }

    public function store(StoreLoanGroupRequest $request)
    {
        $validated = $request->validated();
        $members = $validated['applicants'] ?? [];

        $loanGroup = $this->groups->create($validated, $members);

        return redirect()->route('loan-groups.show', $loanGroup)
            ->with('success', __('messages.loan_group_created'));
    }

    public function show(LoanGroup $loanGroup)
    {
        $loanGroup->load(['applicants', 'loans']);

        return view('loan_groups.show', compact('loanGroup'));
    }

    public function edit(Request $request, LoanGroup $loanGroup)
    {
        $loanGroup->load('applicants');
        $regionId = $request->query('region_id');
        $applicants = $this->groups->editableApplicants(
            $loanGroup,
            $regionId ? (int) $regionId : null
        );

        return view('loan_groups.edit', compact('loanGroup', 'applicants', 'regionId'));
    }

    public function update(UpdateLoanGroupRequest $request, LoanGroup $loanGroup)
    {
        $validated = $request->validated();
        $members = $request->has('applicants') ? ($validated['applicants'] ?? []) : null;

        $this->groups->update($loanGroup, $validated, $members);

        return redirect()->route('loan-groups.show', $loanGroup)
            ->with('success', __('messages.loan_group_updated'));
    }

    public function destroy(LoanGroup $loanGroup)
    {
        $this->groups->delete($loanGroup);

        return redirect()->route('loan-groups.index')
            ->with('success', __('messages.loan_group_deleted'));
    }
}
