<form method="POST" class="space-y-4" data-edit-member-leader hidden>
    @csrf
    @method('PUT')
    <p class="text-sm text-slate-500 dark:text-zinc-400">{{ __('groups.leader_edit_hint') }}</p>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.first_name') }}</label>
            <input type="text" class="app-input" data-edit-readonly="first_name" readonly>
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.middle_name') }}</label>
            <input type="text" class="app-input" data-edit-readonly="middle_name" readonly>
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.last_name') }}</label>
            <input type="text" class="app-input" data-edit-readonly="last_name" readonly>
        </div>
    </div>
    <div class="wizard-field">
        <label class="app-label">{{ __('applicants.dob') }} @include('partials.required-mark')</label>
        <input type="date" name="dob" class="app-input" data-edit-dob max="{{ now()->subYears(18)->toDateString() }}" required>
        <p class="mt-1.5 text-xs font-medium text-indigo-600" data-edit-age hidden></p>
    </div>
    <div class="wizard-field">
        <label class="app-label">{{ __('applicants.sex') }} @include('partials.required-mark')</label>
        @include('partials.inputs.female-sex-field')
    </div>
    <div class="wizard-field">
        <label class="app-label">{{ __('groups.leadership') }}</label>
        <select name="leadership_role" class="app-select">
            <option value="">{{ __('groups.select_leadership') }}</option>
            @foreach(\App\Support\GroupLeadershipRole::options() as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex justify-end gap-2">
        <button type="button" class="app-btn app-btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-primary">{{ __('common.save') }}</button>
    </div>
</form>

<form method="POST" class="space-y-4" data-edit-member-regular hidden>
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.first_name') }} @include('partials.required-mark')</label>
            <input type="text" name="first_name" class="app-input" required>
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.middle_name') }}</label>
            <input type="text" name="middle_name" class="app-input">
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.last_name') }} @include('partials.required-mark')</label>
            <input type="text" name="last_name" class="app-input" required>
        </div>
    </div>
    <div class="wizard-form-grid wizard-form-grid-2">
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.nin') }} @include('partials.required-mark')</label>
            <input type="text" name="nin" class="app-input" required>
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.dob') }} @include('partials.required-mark')</label>
            <input type="date" name="dob" class="app-input" data-edit-dob max="{{ now()->subYears(18)->toDateString() }}" required>
            <p class="mt-1.5 text-xs font-medium text-indigo-600" data-edit-age hidden></p>
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.sex') }} @include('partials.required-mark')</label>
            <input type="hidden" name="sex" value="Female">
            <input type="text" value="{{ __('applicants.female') }}" readonly class="app-input bg-gray-100 border-gray-200 text-gray-600 cursor-not-allowed">
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.marital_status') }} @include('partials.required-mark')</label>
            <select name="marital_status" class="app-select" required>
                @foreach(\App\Models\Applicant::MARITAL_STATUSES as $status)
                    <option value="{{ $status }}">{{ __('applicants.marital_statuses.'.$status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('common.phone') }} @include('partials.required-mark')</label>
            <input type="text" name="phone" class="app-input" required>
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('common.email') }}</label>
            <input type="email" name="email" class="app-input">
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('groups.leadership') }}</label>
            <select name="leadership_role" class="app-select">
                <option value="">{{ __('groups.select_leadership') }}</option>
                @foreach(\App\Support\GroupLeadershipRole::options() as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="flex justify-end gap-2">
        <button type="button" class="app-btn app-btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-primary">{{ __('common.save') }}</button>
    </div>
</form>
