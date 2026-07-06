<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicantGroupMemberRequest;
use App\Http\Requests\StoreApplicantGroupRequest;
use App\Http\Requests\UpdateApplicantGroupMemberRequest;
use App\Models\LoanGroupMember;
use App\Services\ApplicantGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ApplicantGroupController extends Controller
{
    public function __construct(private ApplicantGroupService $groups) {}

    public function show()
    {
        $this->authorize('create loan application');

        $user = Auth::user();
        $group = $this->groups->groupForUser($user);

        if (! $group) {
            if ($user->applicant?->prefersGroupLoan() && $this->groups->canSetupGroup($user)) {
                return redirect()->route('my-group.create');
            }

            return redirect()->route('loan-applications.index');
        }

        $group->load('members');

        $canManage = $this->groups->userCanManageGroup(Auth::user(), $group);
        $canStartApplication = Auth::user()->can('create loan application')
            && Auth::user()->hasCompletedProfile()
            && ! Auth::user()->hasLoanApplication();

        return view('my_group.show', compact('group', 'canManage', 'canStartApplication'));
    }

    public function create()
    {
        $this->authorize('create loan application');

        $user = Auth::user();

        if (! $user->applicant) {
            return redirect()->route('applicants.create')
                ->withErrors(['error' => __('messages.complete_applicant_profile')]);
        }

        if ($user->hasLoanApplication()) {
            return redirect()->route('loan-applications.index')
                ->withErrors(['error' => __('messages.already_has_application')]);
        }

        if ($user->applicant?->prefersIndividualLoan()) {
            return redirect()->route('loan-applications.index');
        }

        if (! $this->groups->canSetupGroup($user)) {
            return redirect()->route('my-group.show')
                ->with('success', __('messages.group_already_registered'));
        }

        return view('my_group.setup', [
            'applicant' => $user->applicant,
            'initialMembers' => old('members', [
                [
                    'first_name' => '',
                    'middle_name' => '',
                    'last_name' => '',
                    'nin' => '',
                    'dob' => '',
                    'phone' => '',
                    'email' => '',
                    'sex' => '',
                    'marital_status' => '',
                    'leadership_role' => '',
                ],
            ]),
        ]);
    }

    public function store(StoreApplicantGroupRequest $request)
    {
        $user = Auth::user();

        if ($user->hasLoanApplication()) {
            return redirect()->route('loan-applications.index')
                ->withErrors(['error' => __('messages.already_has_application')]);
        }

        if (! $this->groups->canSetupGroup($user)) {
            return redirect()->route('my-group.show')
                ->withErrors(['error' => __('messages.group_already_registered')]);
        }

        try {
            $group = $this->groups->setup(
                $user,
                $request->only(['name', 'registration_number', 'phone', 'email', 'leader']),
                $request->input('members', []),
            );
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'applicant_profile_required') {
                return redirect()->route('applicants.create')
                    ->withErrors(['error' => __('messages.complete_applicant_profile')]);
            }

            throw $e;
        }

        return redirect()->route('my-group.show')
            ->with('success', __('messages.loan_group_created'));
    }

    public function storeMember(StoreApplicantGroupMemberRequest $request): RedirectResponse
    {
        $this->groups->addMember($request->user(), $request->validated());

        return redirect()->route('my-group.show')
            ->with('success', __('messages.group_member_added'));
    }

    public function updateMember(UpdateApplicantGroupMemberRequest $request, LoanGroupMember $member): RedirectResponse
    {
        $this->groups->assertMemberBelongsToUserGroup($request->user(), $member);
        $this->groups->updateMember($request->user(), $member, $request->validated());

        return redirect()->route('my-group.show')
            ->with('success', __('messages.group_member_updated'));
    }

    public function destroyMember(LoanGroupMember $member): RedirectResponse
    {
        $this->authorize('create loan application');
        $this->groups->assertMemberBelongsToUserGroup(Auth::user(), $member);

        try {
            $this->groups->removeMember(Auth::user(), $member);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'cannot_remove_leader') {
                return redirect()->route('my-group.show')
                    ->withErrors(['error' => __('messages.cannot_remove_group_leader')]);
            }

            throw $e;
        }

        return redirect()->route('my-group.show')
            ->with('success', __('messages.group_member_removed'));
    }
}
