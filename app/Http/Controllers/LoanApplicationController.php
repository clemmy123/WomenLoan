<?php

namespace App\Http\Controllers;

use App\Http\Requests\Loan\StoreLoanApplicationRequest;
use App\Http\Requests\Loan\UpdateLoanApplicationRequest;
use App\Models\DraftLoan;
use App\Models\Loan;
use App\Models\LoanGroup;
use App\Rules\TanzanianNin;
use App\Services\ApplicantGroupService;
use App\Services\BusinessSectorService;
use App\Services\GeoHierarchyService;
use App\Services\LoanApplicationService;
use App\Services\LoanQueryService;
use App\Support\IdentityNormalizer;
use App\Support\LoanWizardFieldMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanApplicationController extends Controller
{
    public function __construct(
        private LoanApplicationService $applications,
        private LoanQueryService $loans,
        private GeoHierarchyService $geo,
        private BusinessSectorService $businessSectors,
        private ApplicantGroupService $applicantGroups,
    ) {}

    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $this->loans->normalizeListStatus($request->query('status'));
        $sort = 'newest';

        $loans = $this->loans->paginatedIndex($search, $sort, $status);
        $listStatusOptions = $this->loans->listStatusOptions();
        $actionableCount = $this->loans->countActionableForUser(Auth::user());
        $drafts = DraftLoan::where('user_id', Auth::id())->latest()->get();
        $canStartNew = Auth::user()->can('create loan application')
            && Auth::user()->hasCompletedProfile()
            && ! $this->loans->userHasLoanApplication(Auth::user());
        $userGroup = $this->applicantGroups->groupForUser(Auth::user());
        $canSetupGroup = $this->applicantGroups->canSetupGroup(Auth::user());
        $preferredLoanType = Auth::user()->applicant?->preferred_loan_type;

        return view('loan_applications.index', compact(
            'loans',
            'drafts',
            'canStartNew',
            'userGroup',
            'canSetupGroup',
            'preferredLoanType',
            'search',
            'status',
            'listStatusOptions',
            'actionableCount',
        ));
    }

    public function create(Request $request)
    {
        $this->authorize('create loan application');

        $user = Auth::user();

        if (! $user->hasCompletedProfile()) {
            return redirect()->route('applicants.create')
                ->withErrors(['error' => __('messages.complete_applicant_profile')]);
        }

        if ($this->loans->userHasLoanApplication($user)) {
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
            isDraft: $draft !== null,
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
            if ($this->loans->userHasLoanApplication($user)) {
                return redirect()->route('loan-applications.index')
                    ->withErrors(['error' => __('messages.already_has_application')]);
            }

            $this->applications->saveDraft($request, $user->id, $trackId);

            $savedStep = max(1, min(6, (int) $request->input('step', 1)));

            return redirect()
                ->route('loan-applications.create', [
                    'resume_track_id' => $trackId,
                    'wizard_step' => $savedStep,
                ])
                ->with('success', __('messages.draft_saved'))
                ->withInput($request->except([
                    'business_proposal_document',
                    'business_registration_attachment',
                    'proof_address_attachment',
                    'application_letter',
                    'bank_statement',
                    'group_constitution',
                    'group_muhtasari',
                    'group_certificate',
                    'guarantor_letter',
                    '_token',
                ]));
        }

        $formRequest = StoreLoanApplicationRequest::createFrom($request);
        $formRequest->setContainer(app())->setRedirector(app('redirect'));
        $formRequest->validateResolved();

        if ($this->loans->userHasLoanApplication($user)) {
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

        if ($request->input('form_action') === 'submit_to_ward') {
            $this->applications->markSubmittedToWard($loan);

            return redirect()->route('loan-applications.index')
                ->with('success', __('messages.application_submitted'));
        }

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
        $request->validate([
            'step' => ['nullable', 'integer', 'min:1', 'max:6'],
            'track_id' => ['nullable', 'string', 'max:32'],
            'loan_type' => ['nullable', 'string', 'in:individual,group'],
        ]);

        $trackId = $this->applications->saveDraft($request, Auth::id(), $request->input('track_id'));

        return response()->json(['success' => true, 'track_id' => $trackId]);
    }

    public function finalizeApplication(Loan $loan)
    {
        return redirect()->route('loan-applications.show', $loan);
    }

    public function getApplicantByNin(string $nin)
    {
        abort_unless(Auth::user()->can('create loan application'), 403);

        validator(['nin' => $nin], [
            'nin' => ['required', 'string', new TanzanianNin],
        ])->validate();

        $applicant = $this->applications->findApplicantByNin(IdentityNormalizer::normalizeNin($nin));

        if (! $applicant) {
            return response()->json(null, 404);
        }

        return response()->json([
            'id' => $applicant->hashid,
            'full_name' => $applicant->full_name,
            'preferred_loan_type' => $applicant->preferred_loan_type,
        ]);
    }

    public function getGroupMembers(string $groupId)
    {
        abort_unless(Auth::user()->can('create loan application'), 403);

        $group = LoanGroup::findByHashidOrFail($groupId);

        if (! Auth::user()->can('manage loan groups')) {
            $ownGroup = app(ApplicantGroupService::class)->groupForUser(Auth::user());

            if (! $ownGroup || (int) $ownGroup->id !== (int) $group->id) {
                abort(403);
            }
        }

        $members = $this->applications->groupMembers($group->id)->applicants;

        return response()->json($members->map(fn ($applicant) => [
            'id' => $applicant->hashid,
            'full_name' => $applicant->full_name,
            'marital_status' => $applicant->marital_status,
        ])->values());
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
        bool $isDraft = false,
    ): array {
        $user = Auth::user();
        $editing = $editingLoan !== null;

        if ($editing) {
            $trackId = $editingLoan->loan_track_id;
            $formData = $this->applications->formDataFromLoan($editingLoan);
        }

        $wizardStep = max(1, min(6, (int) old(
            'step',
            request()->query('wizard_step', $formData['step'] ?? 1),
        )));

        $validationStepHint = $this->validationStepHint();
        if ($validationStepHint !== null) {
            $wizardStep = $validationStepHint['step'];
        }

        if ($editing && $editingLoan?->status === 'pending' && ! request()->has('wizard_step') && ! old('step') && $validationStepHint === null) {
            $wizardStep = 6;
        }

        $regions = $this->geo->regions();
        $businessSectors = $this->businessSectors->sectors();
        $banks = config('banks.names', []);
        $groups = LoanGroup::query()->orderBy('name')->get(['id', 'name']);
        $applicant = $user->applicant;
        $userGroup = $editing
            ? $editingLoan?->group?->load('members')
            : $this->applicantGroups->groupForUser($user);
        $canSetupGroup = $this->applicantGroups->canSetupGroup($user);
        $canSubmitToWard = ! $editing || $editingLoan?->status === 'pending';

        $wizardConfig = [
            'step' => $wizardStep,
            'totalSteps' => 6,
            'editing' => $editing,
            'isDraft' => $isDraft,
            'selectedRegion' => $this->stringOrNull(old('region_id', $formData['region_id'] ?? null)),
            'selectedDistrict' => $this->stringOrNull(old('district_id', $formData['district_id'] ?? null)),
            'selectedCouncil' => $this->stringOrNull(old('council_id', $formData['council_id'] ?? null)),
            'selectedWard' => $this->stringOrNull(old('ward_id', $formData['ward_id'] ?? null)),
            'selectedStreet' => $this->stringOrNull(old('street_id', $formData['street_id'] ?? null)),
            'guarantorRegion' => $this->stringOrNull(old('guarantor_region_id', $formData['guarantor_region_id'] ?? null)),
            'guarantorDistrict' => $this->stringOrNull(old('guarantor_district_id', $formData['guarantor_district_id'] ?? null)),
            'guarantorCouncil' => $this->stringOrNull(old('guarantor_council_id', $formData['guarantor_council_id'] ?? null)),
            'guarantorWard' => $this->stringOrNull(old('guarantor_ward_id', $formData['guarantor_ward_id'] ?? null)),
            'guarantorStreet' => $this->stringOrNull(old('guarantor_street_id', $formData['guarantor_street_id'] ?? null)),
            'loanType' => old('loan_type', $formData['loan_type'] ?? $applicant?->preferred_loan_type ?? ($editingLoan?->loan_type ?? '')),
            'selectedBusinessSector' => old('business_sector', $formData['business_sector'] ?? ''),
            'selectedBusinessType' => old('business_type', $formData['business_type'] ?? ''),
            'businessCatalog' => $this->businessSectors->wizardCatalog(),
            'geoApi' => GeoHierarchyService::apiUrls(),
            'i18n' => [
                'load_failed' => __('loans.load_failed'),
                'loading' => __('loans.loading_data'),
                'step' => __('common.step_n_of', ['step' => ':step', 'total' => 6]),
                'step_required' => __('loans.step_required'),
                'document_required' => __('common.document_required'),
                'file_too_large' => __('common.file_too_large', ['max' => '1MB']),
                'business_proposal' => __('loans.business_proposal'),
                'business_registration' => __('loans.business_registration'),
                'proof_address' => __('loans.proof_address'),
                'application_letter' => __('loans.application_letter'),
                'bank_statement' => __('loans.bank_statement'),
                'guarantor_letter' => __('loans.guarantor_letter'),
                'group_constitution' => __('loans.group_constitution'),
                'group_muhtasari' => __('loans.group_muhtasari'),
                'group_certificate' => __('loans.group_certificate'),
                'yes' => __('common.yes'),
                'no' => __('common.no'),
                'preview_status_draft' => __('loans.preview_status_draft'),
                'preview_status_pending' => __('loans.preview_status_pending'),
                'loan_type_individual' => __('loans.continue_as_individual'),
                'loan_type_group' => __('loans.continue_as_group'),
                'declaration_confirmed' => __('loans.declaration_confirmed'),
            ],
        ];

        return compact(
            'regions', 'businessSectors', 'banks', 'groups', 'trackId', 'applicant', 'wizardConfig', 'formData',
            'editing', 'editingLoan', 'userGroup', 'canSetupGroup', 'isDraft', 'wizardStep', 'canSubmitToWard',
            'validationStepHint',
        );
    }

    private function validationStepHint(): ?array
    {
        $errors = session('errors');

        if (! $errors || ! $errors->any()) {
            return null;
        }

        $firstField = $errors->keys()[0] ?? null;

        if ($firstField === null || $firstField === 'error') {
            return null;
        }

        $step = LoanWizardFieldMap::stepForField($firstField);

        return [
            'step' => $step,
            'message' => __('messages.validation_check_step', [
                'step' => $step,
                'title' => __('loans.wizard_steps.'.$step),
                'field' => validation_attribute_label($firstField),
            ]),
        ];
    }

    private function stringOrNull(mixed $value): ?string
    {
        return filled($value) ? (string) $value : null;
    }
}
