@php
    $previewNa = __('common.na');
@endphp

<div class="wizard-preview">
    <div class="wizard-preview-banner">
        <div>
            <p class="wizard-preview-banner__label">{{ __('dashboard.track_id') }}</p>
            <p class="wizard-preview-banner__track">{{ $trackId }}</p>
        </div>
        <div class="wizard-preview-banner__meta">
            <span class="wizard-preview-pill" x-text="preview.loan_type_label || '{{ $previewNa }}'"></span>
            <span class="wizard-preview-pill wizard-preview-pill--muted" x-text="preview.status_label"></span>
        </div>
    </div>

    <p class="wizard-preview-intro">{{ __('loans.preview_hint') }}</p>

    <section class="wizard-preview-section">
        <header class="wizard-preview-section__header">
            <span class="wizard-preview-section__icon">@include('partials.wizard-step-icon', ['icon' => 'business'])</span>
            <h4 class="wizard-preview-section__title">{{ __('loans.wizard_steps.1') }}</h4>
        </header>
        <dl class="wizard-preview-list">
            <div class="wizard-preview-item"><dt>{{ __('loans.business_name') }}</dt><dd x-text="preview.business_name || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.business_phone') }}</dt><dd x-text="preview.business_phone || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.business_email') }}</dt><dd x-text="preview.business_email || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.business_sector') }}</dt><dd x-text="preview.business_sector || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.business_type') }}</dt><dd x-text="preview.business_type || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.tin_number') }}</dt><dd class="wizard-preview-mono" x-text="preview.tin_number || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item wizard-preview-item--full"><dt>{{ __('loans.business_location') }}</dt><dd x-text="preview.business_location || '{{ $previewNa }}'"></dd></div>
        </dl>
    </section>

    <section class="wizard-preview-section">
        <header class="wizard-preview-section__header">
            <span class="wizard-preview-section__icon">@include('partials.wizard-step-icon', ['icon' => 'guarantor'])</span>
            <h4 class="wizard-preview-section__title">{{ __('loans.wizard_steps.2') }}</h4>
        </header>
        <dl class="wizard-preview-list">
            <div class="wizard-preview-item"><dt>{{ __('applicants.first_name') }}</dt><dd x-text="preview.guarantor_first_name || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item"><dt>{{ __('applicants.middle_name') }}</dt><dd x-text="preview.guarantor_middle_name || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item"><dt>{{ __('applicants.last_name') }}</dt><dd x-text="preview.guarantor_last_name || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.guarantor_phone') }}</dt><dd x-text="preview.guarantor_phone || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.guarantor_nin') }}</dt><dd class="wizard-preview-mono" x-text="preview.guarantor_nin || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.guarantor_relationship') }}</dt><dd x-text="preview.guarantor_relationship || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.guarantor_occupation') }}</dt><dd x-text="preview.guarantor_occupation || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.guarantor_sex') }}</dt><dd x-text="preview.guarantor_sex || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item wizard-preview-item--full"><dt>{{ __('loans.guarantor_location') }}</dt><dd x-text="preview.guarantor_location || '{{ $previewNa }}'"></dd></div>
        </dl>
    </section>

    <section class="wizard-preview-section">
        <header class="wizard-preview-section__header">
            <span class="wizard-preview-section__icon">@include('partials.wizard-step-icon', ['icon' => 'amount'])</span>
            <h4 class="wizard-preview-section__title">{{ __('loans.wizard_steps.3') }}</h4>
        </header>
        <dl class="wizard-preview-list">
            <div class="wizard-preview-item"><dt>{{ __('loans.requested_amount') }}</dt><dd class="wizard-preview-amount" x-text="preview.requested_amount || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.has_disability') }}</dt><dd x-text="preview.has_disability || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.is_widowed') }}</dt><dd x-text="preview.is_widowed || '{{ $previewNa }}'"></dd></div>
        </dl>
    </section>

    <section class="wizard-preview-section">
        <header class="wizard-preview-section__header">
            <span class="wizard-preview-section__icon">@include('partials.wizard-step-icon', ['icon' => 'bank'])</span>
            <h4 class="wizard-preview-section__title">{{ __('loans.wizard_steps.4') }}</h4>
        </header>
        <dl class="wizard-preview-list">
            <div class="wizard-preview-item"><dt>{{ __('loans.bank_name') }}</dt><dd x-text="preview.bank_name || '{{ $previewNa }}'"></dd></div>
            <div class="wizard-preview-item"><dt>{{ __('loans.bank_number') }}</dt><dd class="wizard-preview-mono" x-text="preview.bank_number || '{{ $previewNa }}'"></dd></div>
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
                <dd x-text="preview.declaration || '{{ $previewNa }}'"></dd>
            </div>
        </dl>
    </section>

    <section class="wizard-preview-section">
        <header class="wizard-preview-section__header">
            <span class="wizard-preview-section__icon">@include('partials.wizard-step-icon', ['icon' => 'review'])</span>
            <h4 class="wizard-preview-section__title">{{ __('loans.documents') }}</h4>
        </header>
        <ul class="wizard-preview-documents">
            <template x-for="doc in preview.documents" :key="doc.label">
                <li class="wizard-preview-document" :class="doc.attached ? 'is-attached' : 'is-missing'">
                    <span class="wizard-preview-document__label" x-text="doc.label"></span>
                    <span class="wizard-preview-document__status" x-text="doc.attached ? @js(__('loans.document_attached')) : @js(__('loans.document_missing'))"></span>
                </li>
            </template>
        </ul>
    </section>
</div>
