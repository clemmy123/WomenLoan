<?php

namespace App\Http\Controllers;

use App\Http\Requests\Loan\StoreLoanApplicationRequest;
use App\Http\Requests\Loan\UpdateLoanApplicationRequest;
use App\Models\DraftLoan;
use App\Models\Loan;
use App\Models\LoanGroup;
use App\Services\ApplicantGroupService;
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
        private ApplicantGroupService $applicantGroups,
    ) {}

    public function index()
    {
        $loans = $this->loans->paginatedIndex();
        $drafts = DraftLoan::where('user_id', Auth::id())->latest()->get();
        $canStartNew = Auth::user()->can('create loan application')
            && ! $this->loans->userHasActiveLoan(Auth::user());
        $userGroup = $this->applicantGroups->groupForUser(Auth::user());
        $canSetupGroup = $this->applicantGroups->canSetupGroup(Auth::user());

        return view('loan_applications.index', compact('loans', 'drafts', 'canStartNew', 'userGroup', 'canSetupGroup'));
    }

    public function create(Request $request)
    {
        $this->authorize('create loan application');

        $user = Auth::user();

        if ($this->loans->userHasActiveLoan($user)) {
            return redirect()->route('loan-applications.index')
                ->withErrors(['error' => __('messages.already_has_application')]);
        }

        $trackId = $request->query('resume_track_id') ?? old('track_id');
        $draft = $trackId
            ? DraftLoan::where('user_id', $user->id)->where('track_id', $trackId)->first()
            : null;

        return view('loan_applications.apply', $this->wizardViewData(
            formData: $draft?->form_data ?? [],
            trackId: $trackId ?? $this->applications->nextTrackId(),
        ));
    }

    public function edit(Loan $loan)
    {
        $this->authorize('create loan application');
        $this->authorizeEditableLoan($loan);

        return view('loan_applications.apply', $this->wizardViewData(
            editingLoan: $loan,
        ));
    }

    public function store(Request $request)
    {
        $this->authorize('create loan application');

        $action = $request->input('form_action');
        $trackId = $request->input('track_id') ?? $this->applications->nextTrackId();
        $user = Auth::user();

        if ($action === 'save_draft') {
            $this->applications->saveDraft($request, $user->id, $trackId);

            return redirect()
                ->route('loan-applications.create', ['resume_track_id' => $trackId])
                ->with('success', __('messages.draft_saved'))
                ->withInput($request->except([
                    'business_proposal_document',
                    'business_registration_attachment',
                    'application_letter',
                    'bank_statement',
                    'group_constitution',
                    'group_muhtasari',
                    'group_certificate',
                    '_token',
                ]));
        }

        $formRequest = StoreLoanApplicationRequest::createFrom($request);
        $formRequest->setContainer(app())->setRedirector(app('redirect'));
        $formRequest->validateResolved();

        if ($this->loans->userHasActiveLoan($user)) {
            return redirect()->route('loan-applications.index')
                ->withErrors(['error' => __('messages.already_has_application')]);
        }

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

    public function update(Request $request, Loan $loan)
    {
        $this->authorize('create loan application');
        $this->authorizeEditableLoan($loan);

        $formRequest = UpdateLoanApplicationRequest::createFrom($request);
        $formRequest->setContainer(app())->setRedirector(app('redirect'));
        $formRequest->validateResolved();

        $this->applications->update($formRequest, $loan);

        return redirect()->route('loan-applications.show', $loan)
            ->with('success', __('messages.application_updated'));
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

    private function authorizeEditableLoan(Loan $loan): void
    {
        if (! $loan->isEditableByApplicant(Auth::user())) {
            abort(403, __('messages.application_not_editable'));
        }
    }

    private function wizardViewData(
        ?Loan $editingLoan = null,
        ?string $trackId = null,
        array $formData = [],
    ): array {
        $user = Auth::user();
        $editing = $editingLoan !== null;

        if ($editing) {
            $trackId = $editingLoan->loan_track_id;
            $formData = $this->applications->formDataFromLoan($editingLoan);
        }

        $regions = $this->geo->regions();
        $groups = LoanGroup::query()->orderBy('name')->get(['id', 'name']);
        $applicant = $user->applicant;
        $userGroup = $editing
            ? $editingLoan?->group?->load('members')
            : $this->applicantGroups->groupForUser($user);
        $canSetupGroup = $this->applicantGroups->canSetupGroup($user);

        $wizardConfig = [
            'step' => (int) old('step', $formData['step'] ?? 1),
            'totalSteps' => 7,
            'selectedRegion' => $this->stringOrNull(old('region_id', $formData['region_id'] ?? null)),
            'selectedDistrict' => $this->stringOrNull(old('district_id', $formData['district_id'] ?? null)),
            'selectedCouncil' => $this->stringOrNull(old('council_id', $formData['council_id'] ?? null)),
            'selectedWard' => $this->stringOrNull(old('ward_id', $formData['ward_id'] ?? null)),
            'selectedStreet' => $this->stringOrNull(old('street_id', $formData['street_id'] ?? null)),
            'loanType' => old('loan_type', $formData['loan_type'] ?? ''),
            'geoApi' => GeoHierarchyService::apiUrls(),
            'i18n' => [
                'load_failed' => __('loans.load_failed'),
                'loading' => __('loans.loading_data'),
                'step' => __('common.step_n_of', ['step' => ':step', 'total' => 7]),
                'step_required' => __('loans.step_required'),
            ],
        ];

        return compact(
            'regions', 'groups', 'trackId', 'applicant', 'wizardConfig', 'formData',
            'editing', 'editingLoan', 'userGroup', 'canSetupGroup',
        );
    }

    private function stringOrNull(mixed $value): ?string
    {
        return filled($value) ? (string) $value : null;
    }
}
