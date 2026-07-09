<form action="{{ $action }}" method="POST" class="space-y-4">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.first_name') }} @include('partials.required-mark')</label>
            <input type="text" name="first_name" value="{{ old('first_name') }}" required class="app-input">
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.middle_name') }}</label>
            <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="app-input">
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.last_name') }} @include('partials.required-mark')</label>
            <input type="text" name="last_name" value="{{ old('last_name') }}" required class="app-input">
        </div>
    </div>
    <div class="wizard-form-grid wizard-form-grid-2">
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.nin') }} @include('partials.required-mark')</label>
            @include('partials.inputs.nin-input', [
                'name' => 'nin',
                'value' => old('nin'),
                'class' => 'w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm',
            ])
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.dob') }} @include('partials.required-mark')</label>
            <input
                type="date"
                name="dob"
                id="member_dob"
                value="{{ old('dob') }}"
                data-age-display="member_dob_age"
                max="{{ now()->subYears(18)->toDateString() }}"
                required
                class="app-input"
            >
            <p
                id="member_dob_age"
                class="mt-1.5 text-xs font-medium text-indigo-600"
                data-age-template="{{ __('applicants.age_years', ['age' => ':age']) }}"
                data-age-empty=""
                @unless(old('dob')) hidden @endunless
            >
                @if(old('dob'))
                    {{ __('applicants.age_years', ['age' => \App\Support\AgeCalculator::years(\Carbon\Carbon::parse(old('dob'))) ?? '—']) }}
                @endif
            </p>
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.sex') }} @include('partials.required-mark')</label>
            @include('partials.inputs.female-sex-field')
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.marital_status') }} @include('partials.required-mark')</label>
            @include('partials.inputs.marital-status-select', ['required' => true])
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('common.phone') }} @include('partials.required-mark')</label>
            @include('partials.inputs.phone-input', [
                'name' => 'phone',
                'value' => old('phone'),
            ])
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('common.email') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" class="app-input">
        </div>
        @include('partials.inputs.leadership-role-select')
    </div>
    <div class="flex justify-end gap-2">
        <button type="button" @click="modal = null" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-primary">{{ __('common.save') }}</button>
    </div>
</form>
