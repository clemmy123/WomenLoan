@php $user = auth()->user(); $step = $loan->current_step; @endphp

<div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-4">
    <h3 class="font-bold text-slate-900">{{ __('workflow.title') }}</h3>

    @if($user->can('receive application') && $step === 1 && $loan->status === 'pending')
    <form method="POST" action="{{ route('loans.workflow', $loan) }}" class="space-y-2">
        @csrf
        <input type="hidden" name="action" value="receive">
        <textarea name="comments" rows="2" placeholder="{{ __('workflow.comments_optional') }}" class="w-full text-sm rounded-xl border border-slate-200 px-3 py-2"></textarea>
        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-2 rounded-xl">{{ __('workflow.buttons.receive') }}</button>
    </form>
    @endif

    @if($user->can('forward to ministry') && $step === 1 && $loan->status === 'received')
    <form method="POST" action="{{ route('loans.workflow', $loan) }}" enctype="multipart/form-data" class="space-y-2">
        @csrf
        <input type="hidden" name="action" value="forward_ministry">
        <textarea name="comments" rows="2" placeholder="{{ __('workflow.review_comments') }}" class="w-full text-sm rounded-xl border border-slate-200 px-3 py-2"></textarea>
        <input type="file" name="attachment" class="w-full text-xs">
        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 rounded-xl">{{ __('workflow.buttons.forward_ministry') }}</button>
    </form>
    @endif

    @if($user->can('propose loan amount') && in_array($step, [2, 4]))
    <form method="POST" action="{{ route('loans.workflow', $loan) }}" class="space-y-2">
        @csrf
        <input type="hidden" name="action" value="propose_amount">
        <input type="number" name="proposed_amount" placeholder="{{ __('workflow.proposed_amount_placeholder') }}" required class="w-full text-sm rounded-xl border border-slate-200 px-3 py-2">
        <textarea name="comments" rows="2" placeholder="{{ __('workflow.comments') }}" class="w-full text-sm rounded-xl border border-slate-200 px-3 py-2"></textarea>
        <button type="submit" class="w-full bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold py-2 rounded-xl">{{ __('workflow.buttons.propose_amount') }}</button>
    </form>
    @endif

    @if($user->hasRole('applicant') && $step === 3)
    <div class="space-y-2">
        <p class="text-sm text-slate-600">{!! __('workflow.proposed_amount', ['amount' => '<strong>'.e(format_tzs($loan->proposed_amount)).'</strong>']) !!}</p>
        <form method="POST" action="{{ route('loans.workflow', $loan) }}">
            @csrf
            <input type="hidden" name="action" value="accept_amount">
            <button type="submit" class="w-full bg-emerald-600 text-white text-sm font-semibold py-2 rounded-xl mb-2">{{ __('workflow.buttons.accept_amount') }}</button>
        </form>
        <form method="POST" action="{{ route('loans.workflow', $loan) }}">
            @csrf
            <input type="hidden" name="action" value="decline_amount">
            <textarea name="comments" rows="2" placeholder="{{ __('workflow.decline_reason') }}" class="w-full text-sm rounded-xl border border-slate-200 px-3 py-2 mb-2"></textarea>
            <button type="submit" class="w-full bg-red-600 text-white text-sm font-semibold py-2 rounded-xl">{{ __('workflow.buttons.decline_amount') }}</button>
        </form>
    </div>
    @endif

    @if($user->can('forward to assistant director') && $step === 4)
    <form method="POST" action="{{ route('loans.workflow', $loan) }}" enctype="multipart/form-data" class="space-y-2">
        @csrf
        <input type="hidden" name="action" value="forward_ass_dir">
        <textarea name="comments" rows="2" class="w-full text-sm rounded-xl border border-slate-200 px-3 py-2"></textarea>
        <input type="file" name="attachment" class="w-full text-xs">
        <button type="submit" class="w-full bg-indigo-600 text-white text-sm font-semibold py-2 rounded-xl">{{ __('workflow.buttons.forward_ass_dir') }}</button>
    </form>
    @endif

    @if($user->can('forward to director') && $step === 5)
    <form method="POST" action="{{ route('loans.workflow', $loan) }}">
        @csrf
        <input type="hidden" name="action" value="forward_director">
        <textarea name="comments" rows="2" placeholder="{{ __('workflow.your_comment') }}" required class="w-full text-sm rounded-xl border border-slate-200 px-3 py-2 mb-2"></textarea>
        <button type="submit" class="w-full bg-indigo-600 text-white text-sm font-semibold py-2 rounded-xl">{{ __('workflow.buttons.forward_director') }}</button>
    </form>
    @endif

    @if($user->can('forward to km') && $step === 6)
    <form method="POST" action="{{ route('loans.workflow', $loan) }}">
        @csrf
        <input type="hidden" name="action" value="forward_km">
        <textarea name="comments" rows="2" placeholder="{{ __('workflow.director_comment') }}" required class="w-full text-sm rounded-xl border border-slate-200 px-3 py-2 mb-2"></textarea>
        <button type="submit" class="w-full bg-indigo-600 text-white text-sm font-semibold py-2 rounded-xl">{{ __('workflow.buttons.forward_km') }}</button>
    </form>
    @endif

    @if($user->can('approve as km') && $step === 7)
    <form method="POST" action="{{ route('loans.workflow', $loan) }}">
        @csrf
        <input type="hidden" name="action" value="approve_km">
        <textarea name="comments" rows="2" class="w-full text-sm rounded-xl border border-slate-200 px-3 py-2 mb-2"></textarea>
        <button type="submit" class="w-full bg-emerald-600 text-white text-sm font-semibold py-2 rounded-xl">{{ __('workflow.buttons.approve_km') }}</button>
    </form>
    @endif

    @if($user->can('assign accountant') && $step === 8)
    <form method="POST" action="{{ route('loans.workflow', $loan) }}" class="space-y-2">
        @csrf
        <input type="hidden" name="action" value="assign_accountant">
        <select name="accountant_id" required class="w-full text-sm rounded-xl border border-slate-200 px-3 py-2">
            <option value="">{{ __('workflow.select_accountant') }}</option>
            @foreach($accountants as $acc)
                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="w-full bg-indigo-600 text-white text-sm font-semibold py-2 rounded-xl">{{ __('workflow.buttons.assign_accountant') }}</button>
    </form>
    @endif

    @if($user->can('disburse loan') && $step === 9 && $loan->officer_id === $user->id)
    <form method="POST" action="{{ route('loans.workflow', $loan) }}" class="space-y-2">
        @csrf
        <input type="hidden" name="action" value="disburse">
        <input type="number" name="disbursed_amount" value="{{ $loan->proposed_amount }}" required class="w-full text-sm rounded-xl border border-slate-200 px-3 py-2">
        <button type="submit" class="w-full bg-emerald-600 text-white text-sm font-semibold py-2 rounded-xl">{{ __('workflow.buttons.disburse', ['amount' => format_tzs($loan->proposed_amount)]) }}</button>
    </form>
    @endif

    @if(!$user->hasAnyRole(['admin','super_admin']) && !(
        ($user->can('receive application') && $step === 1) ||
        ($user->can('propose loan amount') && in_array($step, [2,4])) ||
        ($user->hasRole('applicant') && $step === 3) ||
        ($user->can('forward to assistant director') && $step === 4) ||
        ($user->can('forward to director') && $step === 5) ||
        ($user->can('forward to km') && $step === 6) ||
        ($user->can('approve as km') && $step === 7) ||
        ($user->can('assign accountant') && $step === 8) ||
        ($user->can('disburse loan') && $step === 9 && $loan->officer_id === $user->id)
    ))
        <p class="text-xs text-slate-500">{{ __('workflow.no_actions') }}</p>
    @endif
</div>
