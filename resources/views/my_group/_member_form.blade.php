<form action="{{ $action }}" method="POST" class="space-y-4">
    @csrf
    <div class="wizard-form-grid wizard-form-grid-2">
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.first_name') }} <span class="text-red-500">*</span></label>
            <input type="text" name="first_name" value="{{ old('first_name') }}" required class="app-input">
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.middle_name') }}</label>
            <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="app-input">
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.last_name') }} <span class="text-red-500">*</span></label>
            <input type="text" name="last_name" value="{{ old('last_name') }}" required class="app-input">
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.nin') }} <span class="text-red-500">*</span></label>
            <input type="text" name="nin" value="{{ old('nin') }}" required class="app-input">
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('groups.member_age') }} <span class="text-red-500">*</span></label>
            <input type="number" name="age" min="18" max="120" value="{{ old('age') }}" required class="app-input">
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('applicants.sex') }} <span class="text-red-500">*</span></label>
            <select name="sex" required class="app-select">
                <option value="">{{ __('groups.select_gender') }}</option>
                <option value="Female" @selected(old('sex') === 'Female')>{{ __('groups.sex_female') }}</option>
                <option value="Male" @selected(old('sex') === 'Male')>{{ __('groups.sex_male') }}</option>
            </select>
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('common.phone') }} <span class="text-red-500">*</span></label>
            <input type="text" name="phone" value="{{ old('phone') }}" required class="app-input">
        </div>
        <div class="wizard-field">
            <label class="app-label">{{ __('common.email') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" class="app-input">
        </div>
    </div>
    <div class="flex justify-end gap-2">
        <button type="button" @click="modal = null" class="app-btn app-btn-secondary">{{ __('common.cancel') }}</button>
        <button type="submit" class="app-btn app-btn-primary">{{ __('groups.add_member') }}</button>
    </div>
</form>
