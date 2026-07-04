@props([
    'name',
    'title',
    'id' => null,
    'accept' => '.pdf,.doc,.docx',
    'required' => false,
    'existing' => null,
    'hint' => null,
    'maxKb' => 1024,
])

@php
    $inputId = $id ?? $name;
    $hintText = $hint ?? __('common.tap_to_upload_max', ['max' => '1MB']);
    $hasExisting = filled($existing);
    $existingName = $hasExisting ? basename($existing) : '';
@endphp

<div {{ $attributes->merge(['class' => 'doc-attachment-field']) }}>
    <label
        for="{{ $inputId }}"
        class="doc-attachment-card doc-attachment-card--upload relative @if($hasExisting) has-file is-uploaded @endif"
        data-uploaded-label="{{ __('common.document_uploaded') }}"
        data-uploaded-as-label="{{ __('common.document_uploaded_as', ['name' => ':name']) }}"
    >
        <span class="doc-attachment-icon" aria-hidden="true">
            @include('partials.icons.document-upload')
        </span>
        <span class="doc-attachment-check" aria-hidden="true">
            <svg class="doc-attachment-check-svg" viewBox="0 0 24 24" fill="none">
                <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
        <span class="doc-attachment-body">
            <span class="doc-attachment-title">
                {{ $title }}
                @if($required)<span class="doc-attachment-required">*</span>@endif
            </span>
            <span class="doc-attachment-hint" data-doc-hint>{{ $hintText }}</span>
            <span class="doc-attachment-uploaded" data-doc-uploaded @unless($hasExisting) hidden @endunless>
                <span data-doc-uploaded-label>{{ __('common.document_uploaded') }}</span>
                <span class="doc-attachment-filename" data-doc-filename>{{ $existingName }}</span>
            </span>
            @if($hasExisting)
                <span class="doc-attachment-existing">{{ __('loans.keep_existing_file') }}</span>
            @endif
            <span class="doc-attachment-size-error" data-doc-size-error hidden></span>
        </span>
        <input
            type="file"
            name="{{ $name }}"
            id="{{ $inputId }}"
            accept="{{ $accept }}"
            class="doc-attachment-input"
            data-max-kb="{{ $maxKb }}"
            @if($required) data-doc-required="true" @endif
            @if($hasExisting) data-has-existing="true" data-existing-name="{{ $existingName }}" @endif
            @if(empty(trim($inputAttributes ?? '')) && $required) required @endif
            {!! $inputAttributes ?? '' !!}
        >
    </label>
    @error($name)
        <p class="doc-attachment-error">{{ $message }}</p>
    @enderror
</div>
