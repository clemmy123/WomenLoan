<?php

namespace App\Http\Controllers;

use App\Http\Requests\Loan\StoreLoanApplicationRequest;
use App\Models\DraftLoan;
use App\Models\Loan;
use App\Models\LoanGroup;
use App\Services\GeoHierarchyService;
use App\Services\LoanApplicationService;
use App\Services\LoanQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanApplicationController extends Controller
{
    public function __construct(
        private LoanApplicationService $applications,
        private LoanQueryService $loans,
        private GeoHierarchyService $geo,
    ) {}

    public function index()
    {
        $loans = $this->loans->paginatedIndex();
        $drafts = DraftLoan::where('user_id', Auth::id())->latest()->get();

        return view('loan_applications.index', compact('loans', 'drafts'));
    }

    public function create(Request $request)
    {
        $this->authorize('create loan application');

        $user = Auth::user();

        if ($this->loans->userHasActiveLoan($user)) {
            return redirect()->route('loan-applications.index')
                ->withErrors(['error' => __('messages.already_has_application')]);
        }

        $regions = $this->geo->regions();
        $groups = LoanGroup::query()->orderBy('name')->get(['id', 'name']);
        $trackId = $request->query('resume_track_id');
        $applicant = $user->applicant;

        return view('loan_applications.apply', compact('regions', 'groups', 'trackId', 'applicant'));
    }

    public function store(Request $request)
    {
        $this->authorize('create loan application');

        $action = $request->input('form_action');
        $trackId = $request->input('track_id') ?? $this->applications->nextTrackId();
        $user = Auth::user();

        if ($action === 'save_draft') {
            $this->applications->saveDraft($request, $user->id, $trackId);

            return redirect()->back()
                ->with('success', __('messages.draft_saved'))
                ->with('track_id', $trackId);
        }

        $formRequest = StoreLoanApplicationRequest::createFrom($request);
        $formRequest->setContainer(app())->setRedirector(app('redirect'));
        $formRequest->validateResolved();

        try {
            $this->applications->submit($formRequest, $user, $trackId);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'applicant_profile_required') {
                return redirect()->route('applicants.create')
                    ->withErrors(['error' => __('messages.complete_applicant_profile')]);
            }

            throw $e;
        }

        return redirect()->route('loan-applications.index')
            ->with('success', __('messages.application_submitted'));
    }

    public function show(Loan $loan)
    {
        $loan = $this->loans->loadForShow($loan);
        $accountants = $this->loans->accountants();

        return view('loan_applications.show', compact('loan', 'accountants'));
    }

    public function saveDraft(Request $request, $id = null)
    {
        $trackId = $this->applications->saveDraft($request, Auth::id(), $request->input('track_id'));

        return response()->json(['success' => true, 'track_id' => $trackId]);
    }

    public function finalizeApplication(Loan $loan)
    {
        return redirect()->route('loan-applications.show', $loan);
    }

    public function getApplicantByNin($nin)
    {
        return response()->json($this->applications->findApplicantByNin($nin));
    }

    public function getGroupMembers(string $groupId)
    {
        $group = LoanGroup::findByHashidOrFail($groupId);

        return response()->json($this->applications->groupMembers($group->id)->applicants);
    }
}
