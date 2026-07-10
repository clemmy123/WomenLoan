@if(loan_needs_user_action($loan))
    <span class="badge badge-warning loan-action-needed-badge" title="{{ __('loans.needs_your_action') }}">
        <span class="loan-action-needed-badge__dot" aria-hidden="true"></span>
        {{ __('loans.action_new') }}
    </span>
@endif
