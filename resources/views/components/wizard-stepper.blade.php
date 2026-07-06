@props(['steps' => []])

<nav class="loan-wizard-stepper" aria-label="{{ __('loans.wizard_title') }}">
    <ol class="loan-wizard-stepper__list">
        @foreach ($steps as $index => $step)
            @php($number = $index + 1)
            <li class="loan-wizard-stepper__item">
                <div class="loan-wizard-stepper__track">
                    @if (! $loop->first)
                        <span
                            class="loan-wizard-stepper__line loan-wizard-stepper__line--before"
                            :class="{ 'is-complete': step > {{ $number - 1 }} }"
                            aria-hidden="true"
                        ></span>
                    @endif

                    <span
                        class="loan-wizard-stepper__node"
                        :class="{
                            'is-complete': step > {{ $number }},
                            'is-active': step === {{ $number }},
                            'is-pending': step < {{ $number }}
                        }"
                        :aria-current="step === {{ $number }} ? 'step' : false"
                    >
                        <span class="loan-wizard-stepper__icon">
                            @include('partials.wizard-step-icon', ['icon' => $step['icon'] ?? 'document'])
                        </span>
                    </span>

                    @if (! $loop->last)
                        <span
                            class="loan-wizard-stepper__line loan-wizard-stepper__line--after"
                            :class="{
                                'is-complete': step > {{ $number }},
                                'is-active': step === {{ $number }}
                            }"
                            aria-hidden="true"
                        ></span>
                    @endif
                </div>

                <div class="loan-wizard-stepper__meta">
                    <p
                        class="loan-wizard-stepper__title"
                        :class="{
                            'is-complete': step > {{ $number }},
                            'is-active': step === {{ $number }},
                            'is-pending': step < {{ $number }}
                        }"
                    >
                        <span class="loan-wizard-stepper__check" x-show="step > {{ $number }}" x-cloak aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <span>{{ $step['title'] }}</span>
                    </p>
                </div>
            </li>
        @endforeach
    </ol>
</nav>
