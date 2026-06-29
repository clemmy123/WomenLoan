<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkflowActionRequest;
use App\Models\Loan;
use App\Services\LoanWorkflowService;
use App\Services\WorkflowAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkflowController extends Controller
{
    public function __construct(
        private LoanWorkflowService $workflow,
        private WorkflowAuthorizationService $authorization,
    ) {}

    public function action(WorkflowActionRequest $request, Loan $loan)
    {
        $user = Auth::user();
        $action = $request->input('action');

        $this->authorization->authorizeOrAbort($user, $loan, $action);

        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('workflow', 'public');
        }

        $this->workflow->process($loan, $action, $data);

        return back()->with('success', __('messages.workflow_action_success'));
    }

    public function track(Request $request)
    {
        $request->validate(['track_id' => 'required|string']);

        $loan = Loan::where('loan_track_id', $request->track_id)->firstOrFail();

        return view('loan_applications.track', compact('loan'));
    }
}
