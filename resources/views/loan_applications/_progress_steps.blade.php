@php
    $currentStep = (int) ($loan->current_step ?? 1);
    $isDisbursed = $loan->status === 'disbursed';
    $totalSteps = 9;
@endphp
<div class="app-card app-card-padded">
    <h3 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2 mb-2">{{ __('loans.progress_steps') }}</h3>
    <p class="text-sm text-slate-500 dark:text-zinc-400 mb-5">{{ __('loans.progress_steps_help') }}</p>

    <ol class="loan-progress-stages space-y-0">
        @for ($step = 1; $step <= $totalSteps; $step++)
            @php
                $isComplete = $isDisbursed || $step < $currentStep;
                $isCurrent = ! $isDisbursed && $step === $currentStep;
                $awaitingApplicant = $isCurrent && $step === 3;
            @endphp
            <li class="loan-progress-stage">
                @if($step > 1)
                    <span @class([
                        'loan-progress-connector',
                        'is-complete' => $isDisbursed || $step - 1 < $currentStep,
                        'is-current' => ! $isDisbursed && $step - 1 === $currentStep,
                    ]) aria-hidden="true"></span>
                @endif

                <div class="flex items-start gap-3">
                    <span @class([
                        'loan-progress-indicator',
                        'is-complete' => $isComplete,
                        'is-current' => $isCurrent,
                        'is-pending' => ! $isComplete && ! $isCurrent,
                    ])>
                        @if($isComplete)
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @elseif($isCurrent)
                            <span class="loan-progress-pulse" aria-hidden="true"></span>
                        @else
                            <span class="loan-progress-dot" aria-hidden="true"></span>
                        @endif
                    </span>

                    <div class="min-w-0 flex-1 {{ $step < $totalSteps ? 'pb-5' : '' }}">
                        <div class="flex flex-wrap items-center gap-2">
                            <p @class([
                                'text-sm font-semibold',
                                'text-slate-900 dark:text-white' => $isComplete || $isCurrent,
                                'text-slate-400 dark:text-zinc-500' => ! $isComplete && ! $isCurrent,
                            ])>
                                {{ loan_workflow_step_label($step) }}
                            </p>

                            @if($isComplete)
                                <span class="loan-progress-badge loan-progress-badge--done">{{ __('loans.progress_status.completed') }}</span>
                            @elseif($awaitingApplicant)
                                <span class="loan-progress-badge loan-progress-badge--action">{{ __('loans.progress_status.awaiting_applicant') }}</span>
                            @elseif($isCurrent)
                                <span class="loan-progress-badge loan-progress-badge--current">{{ __('loans.progress_status.under_review') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </li>
        @endfor
    </ol>
</div>
