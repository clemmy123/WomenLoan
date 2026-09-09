<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkflowActionRequest;
use App\Models\Loan;
use App\Services\LoanTrackService;
use App\Services\LoanWorkflowService;
use App\Services\WorkflowAuthorizationService;
use App\Support\AccessibleHome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class WorkflowController extends Controller
{
    public function __construct(
        private LoanWorkflowService $workflow,
        private WorkflowAuthorizationService $authorization,
        private LoanTrackService $trackService,
    ) {}

    public function action(WorkflowActionRequest $request, string $loan): RedirectResponse
    {
        $user = Auth::user();
        $loan = Loan::findByHashidUnscopedOrFail($loan);
        $action = $request->input('action');

        $this->authorization->authorizeOrAbort($user, $loan, $action);

        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('workflow', 'public');
        }

        $this->workflow->process($loan, $action, $data);

        return $this->redirectAfterWorkflow($user, $loan->fresh());
    }

    public function track(Request $request)
    {
        $user = Auth::user();
        $trackIdInput = $request->query('track_id');

        if (! $trackIdInput) {
            return view('loan_applications.track', [
                'loan' => null,
                'draft' => null,
                'trackId' => null,
                'canViewFullDetails' => false,
                'canResumeDraft' => false,
            ]);
        }

        $request->validate(['track_id' => 'required|string|max:32']);

        $resolved = $this->trackService->resolve($user, $trackIdInput);

        if ($resolved === null) {
            throw ValidationException::withMessages([
                'track_id' => __('loans.track_not_found'),
            ]);
        }

        $loan = $resolved['loan'];
        $draft = $resolved['draft'];
        $trackId = $resolved['trackId'];

        $canViewFullDetails = $loan && $this->trackService->canViewFullLoanDetails($user, $loan);
        $canResumeDraft = $draft && $this->trackService->canResumeDraft($user, $draft);

        return view('loan_applications.track', compact(
            'loan',
            'draft',
            'trackId',
            'canViewFullDetails',
            'canResumeDraft',
        ));
    }

    protected function redirectAfterWorkflow($user, Loan $loan): RedirectResponse
    {
        if (Loan::whereKey($loan->id)->exists()) {
            return redirect()
                ->route('loan-applications.show', $loan)
                ->with('success', __('messages.workflow_action_success'));
        }

        if ($user->hasRole('applicant')) {
            return redirect()
                ->route('loan-applications.index')
                ->with('success', __('messages.workflow_action_success'));
        }

        return redirect()
            ->to(AccessibleHome::url($user))
            ->with('success', __('messages.workflow_action_success'));
    }
}
