@php
    $previewNa = __('common.na');
    $previewVal = function (string $key, mixed $default = '') use ($fd, $previewNa) {
        $value = $fd($key, $default);

        return filled($value) ? $value : $previewNa;
    };
    $geoName = function (?string $class, mixed $id) {
        if (! filled($id) || ! is_string($class)) {
            return '';
        }

        return $class::query()->find($id)?->name ?? '';
    };
    $joinLocation = function (array $parts) {
        return collect($parts)->filter(fn ($part) => filled($part))->implode(' → ');
    };
    $businessLocation = $joinLocation([
        $geoName(\App\Models\Region::class, $fd('region_id')),
        $geoName(\App\Models\District::class, $fd('district_id')),
        $geoName(\App\Models\Council::class, $fd('council_id')),
        $geoName(\App\Models\Ward::class, $fd('ward_id')),
        $geoName(\App\Models\Street::class, $fd('street_id')),
    ]);
    $guarantorLocation = $joinLocation([
        $geoName(\App\Models\Region::class, $fd('guarantor_region_id')),
        $geoName(\App\Models\District::class, $fd('guarantor_district_id')),
        $geoName(\App\Models\Council::class, $fd('guarantor_council_id')),
        $geoName(\App\Models\Ward::class, $fd('guarantor_ward_id')),
        $geoName(\App\Models\Street::class, $fd('guarantor_street_id')),
    ]);
    $relationship = $fd('guarantor_relationship');
    $relationshipLabel = $relationship !== ''
        ? (__('loans.guarantor_relationships')[$relationship] ?? $relationship)
        : $previewNa;
    $sex = $fd('guarantor_sex');
    $sexLabel = match ($sex) {
        'Female' => __('applicants.female'),
        'Male' => __('applicants.male'),
        default => $previewNa,
    };
    $yesNo = function (mixed $value) use ($previewNa) {
        return match ((string) $value) {
            '1' => __('common.yes'),
            '0' => __('common.no'),
            default => $previewNa,
        };
    };
    $amount = $fd('requested_amount');
    $amountLabel = filled($amount) ? 'TZS '.number_format((float) preg_replace('/[^\d.]/', '', (string) $amount)) : $previewNa;
    $loanTypeLabel = ($profileLoanType ?? '') === 'group'
        ? __('loans.continue_as_group')
        : (($profileLoanType ?? '') === 'individual' ? __('loans.continue_as_individual') : $previewNa);
    $statusLabel = ($isDraft ?? false) ? __('loans.preview_status_draft') : __('loans.preview_status_pending');
    $declarationLabel = filter_var($fd('declaration'), FILTER_VALIDATE_BOOLEAN)
        ? __('loans.declaration_confirmed')
        : $previewNa;
    $previewDocs = [
        ['name' => 'business_proposal_document', 'label' => __('loans.business_proposal')],
        ['name' => 'business_registration_attachment', 'label' => __('loans.business_license')],
        ['name' => 'proof_address_attachment', 'label' => __('loans.proof_address')],
        ['name' => 'application_letter', 'label' => __('loans.application_letter')],
        ['name' => 'bank_statement', 'label' => __('loans.bank_statement')],
        ['name' => 'guarantor_letter', 'label' => __('loans.guarantor_letter')],
    ];
    if (($profileLoanType ?? '') === 'group') {
        array_push(
            $previewDocs,
            ['name' => 'group_constitution', 'label' => __('loans.group_constitution')],
            ['name' => 'group_muhtasari', 'label' => __('loans.group_muhtasari')],
            ['name' => 'group_certificate', 'label' => __('loans.group_certificate')],
        );
    }
@endphp

<div class="wizard-preview">
    <div class="wizard-preview-banner">
        <div>
            <p class="wizard-preview-banner__label">{{ __('dashboard.track_id') }}</p>
            <p class="wizard-preview-banner__track">{{ $trackId }}</p>
        </div>
        <div class="wizard-preview-banner__meta">
            <span class="wizard-preview-pill" data-preview="loan_type_label">{{ $loanTypeLabel }}</span>
            <span class="wizard-preview-pill wizard-preview-pill--muted" data-preview="status_label">{{ $statusLabel }}</span>
        </div>
    </div>

    <p class="wizard-preview-intro">{{ __('loans.preview_hint') }}</p>

    <section class="wizard-preview-section">
        <header class="wizard-preview-section__header">
            <span class="wizard-preview-section__icon">@include('partials.wizard-step-icon', ['icon' => 'business'])</span>
            <h4 class="wizard-preview-section__title">{{ __('loans.wizard_steps.1') }}</h4>
        </header>
        <dl class="wizard-preview-list">
            <div class="wizard-preview-item"><dt>{{ __('loans.business_name') }}</dt><dd data-preview="business_name">{{ $previewVal('business_name') }}</dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.business_phone') }}</dt><dd data-preview="business_phone">{{ $previewVal('business_phone') }}</dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.business_email') }} ({{ __('common.optional') }})</dt><dd data-preview="business_email">{{ $previewVal('business_email') }}</dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.business_sector') }}</dt><dd data-preview="business_sector">{{ $previewVal('business_sector') }}</dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.business_type') }}</dt><dd data-preview="business_type">{{ $previewVal('business_type') }}</dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.tin_number') }}</dt><dd class="wizard-preview-mono" data-preview="tin_number">{{ $previewVal('tin_number') }}</dd></div>
            <div class="wizard-preview-item wizard-preview-item--full"><dt>{{ __('loans.business_location') }}</dt><dd data-preview="business_location">{{ $businessLocation !== '' ? $businessLocation : $previewNa }}</dd></div>
        </dl>
    </section>

    <section class="wizard-preview-section">
        <header class="wizard-preview-section__header">
            <span class="wizard-preview-section__icon">@include('partials.wizard-step-icon', ['icon' => 'guarantor'])</span>
            <h4 class="wizard-preview-section__title">{{ __('loans.wizard_steps.2') }}</h4>
        </header>
        <dl class="wizard-preview-list">
            <div class="wizard-preview-item"><dt>{{ __('applicants.first_name') }}</dt><dd data-preview="guarantor_first_name">{{ $previewVal('guarantor_first_name') }}</dd></div>
            <div class="wizard-preview-item"><dt>{{ __('applicants.middle_name') }}</dt><dd data-preview="guarantor_middle_name">{{ $previewVal('guarantor_middle_name') }}</dd></div>
            <div class="wizard-preview-item"><dt>{{ __('applicants.last_name') }}</dt><dd data-preview="guarantor_last_name">{{ $previewVal('guarantor_last_name') }}</dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.guarantor_phone') }}</dt><dd data-preview="guarantor_phone">{{ $previewVal('guarantor_phone') }}</dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.guarantor_nin') }}</dt><dd class="wizard-preview-mono" data-preview="guarantor_nin">{{ $previewVal('guarantor_nin') }}</dd></div>
            @if(($profileLoanType ?? '') === 'individual')
                <div class="wizard-preview-item" data-loan-scope="individual"><dt>{{ __('loans.guarantor_relationship') }}</dt><dd data-preview="guarantor_relationship">{{ $relationshipLabel }}</dd></div>
            @endif
            <div class="wizard-preview-item"><dt>{{ __('loans.guarantor_occupation') }}</dt><dd data-preview="guarantor_occupation">{{ $previewVal('guarantor_occupation') }}</dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.guarantor_sex') }}</dt><dd data-preview="guarantor_sex">{{ $sexLabel }}</dd></div>
            <div class="wizard-preview-item wizard-preview-item--full"><dt>{{ __('loans.guarantor_location') }}</dt><dd data-preview="guarantor_location">{{ $guarantorLocation !== '' ? $guarantorLocation : $previewNa }}</dd></div>
        </dl>
    </section>

    <section class="wizard-preview-section">
        <header class="wizard-preview-section__header">
            <span class="wizard-preview-section__icon">@include('partials.wizard-step-icon', ['icon' => 'amount'])</span>
            <h4 class="wizard-preview-section__title">{{ __('loans.wizard_steps.3') }}</h4>
        </header>
        <dl class="wizard-preview-list">
            <div class="wizard-preview-item"><dt>{{ __('loans.requested_amount') }}</dt><dd class="wizard-preview-amount" data-preview="requested_amount">{{ $amountLabel }}</dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.has_disability') }}</dt><dd data-preview="has_disability">{{ $yesNo($fd('has_disability', $applicant?->has_disability ? '1' : '0')) }}</dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.is_widowed') }}</dt><dd data-preview="is_widowed">{{ $yesNo($fd('is_widowed', $applicant?->isWidowed() ? '1' : '0')) }}</dd></div>
        </dl>
    </section>

    <section class="wizard-preview-section">
        <header class="wizard-preview-section__header">
            <span class="wizard-preview-section__icon">@include('partials.wizard-step-icon', ['icon' => 'bank'])</span>
            <h4 class="wizard-preview-section__title">{{ __('loans.wizard_steps.4') }}</h4>
        </header>
        <dl class="wizard-preview-list">
            <div class="wizard-preview-item"><dt>{{ __('loans.bank_name') }}</dt><dd data-preview="bank_name">{{ $previewVal('bank_name') }}</dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.bank_number') }}</dt><dd class="wizard-preview-mono" data-preview="bank_number">{{ $previewVal('bank_number') }}</dd></div>
        </dl>
    </section>

    <section class="wizard-preview-section">
        <header class="wizard-preview-section__header">
            <span class="wizard-preview-section__icon">@include('partials.wizard-step-icon', ['icon' => 'declaration'])</span>
            <h4 class="wizard-preview-section__title">{{ __('loans.wizard_steps.5') }}</h4>
        </header>
        <dl class="wizard-preview-list">
            <div class="wizard-preview-item wizard-preview-item--full">
                <dt>{{ __('loans.declaration') }}</dt>
                <dd data-preview="declaration">{{ $declarationLabel }}</dd>
            </div>
        </dl>
    </section>

    <section class="wizard-preview-section">
        <header class="wizard-preview-section__header">
            <span class="wizard-preview-section__icon">@include('partials.wizard-step-icon', ['icon' => 'review'])</span>
            <h4 class="wizard-preview-section__title">{{ __('loans.documents') }}</h4>
        </header>
        <ul class="wizard-preview-documents" data-preview-documents>
            @foreach($previewDocs as $doc)
                @php $attached = filled($uploadExisting($doc['name'])); @endphp
                <li class="wizard-preview-document {{ $attached ? 'is-attached' : 'is-missing' }}">
                    <span class="wizard-preview-document__label">{{ $doc['label'] }}</span>
                    <span class="wizard-preview-document__status">{{ $attached ? __('loans.document_attached') : __('loans.document_missing') }}</span>
                </li>
            @endforeach
        </ul>
    </section>
</div>
