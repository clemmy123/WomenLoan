@php
    use App\Models\Applicant;
    use App\Support\IdentityNormalizer;
    use Illuminate\Support\Facades\Storage;

    $isEdit = $applicant?->exists ?? false;
    $lockRegistrationFields = $lockRegistrationFields ?? false;
    $lockNidaFields = $lockNidaFields ?? false;
    $lockedInputClass = 'bg-gray-100 border-gray-200 text-gray-600 cursor-not-allowed focus:ring-0 focus:border-gray-200';
    $dobValue = old('dob', $applicant?->dob
        ? (\Illuminate\Support\Carbon::parse($applicant->dob)->format('Y-m-d'))
        : '');
    $maritalValue = old('marital_status', $applicant?->marital_status ?? '');
    $loanTypeValue = old('preferred_loan_type', $applicant?->preferred_loan_type ?? '');
    $disabilityValue = old('has_disability', $applicant?->has_disability === null ? '' : ($applicant->has_disability ? '1' : '0'));
    $photoUrl = filled($applicant?->photo_path ?? null) && Storage::disk('public')->exists($applicant->photo_path)
        ? \App\Support\SecureFileUrl::forPath($applicant->photo_path)
        : null;
    $ageYears = $dobValue ? \App\Support\AgeCalculator::years(\Carbon\Carbon::parse($dobValue)) : null;
    $selfServiceOnboarding = $selfServiceOnboarding ?? false;
@endphp

@if($selfServiceOnboarding)
    @if($lockNidaFields)
        <input type="hidden" name="nin" value="{{ $applicant->nin }}">
        <input type="hidden" name="first_name" value="{{ $applicant->first_name }}">
        <input type="hidden" name="middle_name" value="{{ $applicant->middle_name }}">
        <input type="hidden" name="last_name" value="{{ $applicant->last_name }}">
        <input type="hidden" name="dob" value="{{ $dobValue }}">
        <input type="hidden" name="nationality" value="{{ $applicant->nationality ?: 'Tanzanian' }}">
        <input type="hidden" name="sex" value="Female">
        <input type="hidden" name="phone" value="{{ old('phone', $applicant?->phone ?? '') }}">
        <input type="hidden" name="email" value="{{ old('email', $applicant?->email ?? '') }}">
    @else
        <input type="hidden" name="first_name" value="{{ old('first_name', $applicant?->first_name ?? '') }}">
        <input type="hidden" name="middle_name" value="{{ old('middle_name', $applicant?->middle_name ?? '') }}">
        <input type="hidden" name="last_name" value="{{ old('last_name', $applicant?->last_name ?? '') }}">
        <input type="hidden" name="nin" value="{{ old('nin', $applicant?->nin ?? '') }}">
        <input type="hidden" name="dob" value="{{ $dobValue }}">
        <input type="hidden" name="phone" value="{{ old('phone', $applicant?->phone ?? '') }}">
        <input type="hidden" name="email" value="{{ old('email', $applicant?->email ?? '') }}">
        <input type="hidden" name="sex" value="{{ old('sex', $applicant?->sex ?? 'Female') }}">
        <input type="hidden" name="nationality" value="{{ old('nationality', $applicant?->nationality ?? 'Tanzanian') }}">
    @endif
@else
<div class="bg-white p-6 rounded-xl border border-gray-200 space-y-6">
    <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">{{ __('applicants.section_identification') }}</h2>

    @if($lockNidaFields)
        <p class="text-xs text-emerald-700 font-medium">{{ __('nida.nida_fields_locked') }}</p>
        <div class="nida-identity-card">
            <div class="nida-identity-header">
                <div class="nida-identity-photo-wrap">
                    @if($photoUrl)
                        <img src="{{ $photoUrl }}" alt="" class="nida-identity-photo" width="112" height="140">
                    @else
                        <div class="nida-identity-photo" aria-hidden="true"></div>
                    @endif
                </div>
                <span class="nida-verified-pill">{{ __('nida.verified_badge') }}</span>
                <p class="nida-identity-fullname">{{ trim(implode(' ', array_filter([$applicant->first_name, $applicant->middle_name, $applicant->last_name]))) }}</p>
            </div>
            <dl class="nida-identity-grid">
                <div class="nida-identity-field">
                    <dt>{{ __('applicants.first_name') }}</dt>
                    <dd>{{ $applicant->first_name }}</dd>
                </div>
                <div class="nida-identity-field">
                    <dt>{{ __('applicants.middle_name') }}</dt>
                    <dd>{{ $applicant->middle_name ?: '—' }}</dd>
                </div>
                <div class="nida-identity-field">
                    <dt>{{ __('applicants.last_name') }}</dt>
                    <dd>{{ $applicant->last_name }}</dd>
                </div>
                <div class="nida-identity-field">
                    <dt>{{ __('applicants.sex') }}</dt>
                    <dd>{{ $applicant->sex ?: __('applicants.female') }}</dd>
                </div>
                <div class="nida-identity-field nida-identity-field--full">
                    <dt>{{ __('applicants.nin') }}</dt>
                    <dd class="nida-mono">{{ IdentityNormalizer::formatNin($applicant->nin) }}</dd>
                </div>
                <div class="nida-identity-field">
                    <dt>{{ __('applicants.dob') }}</dt>
                    <dd>{{ $dobValue }}</dd>
                </div>
                <div class="nida-identity-field">
                    <dt>{{ __('applicants.age') }}</dt>
                    <dd>{{ $ageYears ?? '—' }}</dd>
                </div>
                <div class="nida-identity-field nida-identity-field--full">
                    <dt>{{ __('applicants.nationality') }}</dt>
                    <dd>{{ $applicant->nationality ?: 'Tanzanian' }}</dd>
                </div>
            </dl>
        </div>
        <input type="hidden" name="nin" value="{{ $applicant->nin }}">
        <input type="hidden" name="first_name" value="{{ $applicant->first_name }}">
        <input type="hidden" name="middle_name" value="{{ $applicant->middle_name }}">
        <input type="hidden" name="last_name" value="{{ $applicant->last_name }}">
        <input type="hidden" name="dob" value="{{ $dobValue }}">
        <input type="hidden" name="nationality" value="{{ $applicant->nationality ?: 'Tanzanian' }}">
        <input type="hidden" name="sex" value="Female">
    @else
        @if($lockRegistrationFields)
            <p class="text-xs text-gray-500">{{ __('applicants.registration_fields_locked') }}</p>
        @endif

        <div>
            <label for="nin" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.nin') }} @include('partials.required-mark')</label>
            @include('partials.inputs.nin-input', [
                'name' => 'nin',
                'value' => old('nin', $applicant?->nin ?? ''),
                'class' => 'w-full bg-gray-50 border rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 '.($errors->has('nin') ? 'border-red-500' : 'border-gray-300'),
            ])
            @error('nin') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label for="first_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.first_name') }} @include('partials.required-mark')</label>
                <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $applicant?->first_name ?? '') }}" @readonly($lockRegistrationFields) class="w-full border @error('first_name') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $lockRegistrationFields ? $lockedInputClass : 'bg-gray-50' }}">
                @error('first_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="middle_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.middle_name') }}</label>
                <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name', $applicant?->middle_name ?? '') }}" @readonly($lockRegistrationFields) class="w-full border @error('middle_name') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $lockRegistrationFields ? $lockedInputClass : 'bg-gray-50' }}">
                @error('middle_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="last_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.last_name') }} @include('partials.required-mark')</label>
                <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $applicant?->last_name ?? '') }}" @readonly($lockRegistrationFields) class="w-full border @error('last_name') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $lockRegistrationFields ? $lockedInputClass : 'bg-gray-50' }}">
                @error('last_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
            <div>
                <label for="dob" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.dob') }} @include('partials.required-mark')</label>
                <input
                    type="date"
                    name="dob"
                    id="dob"
                    value="{{ $dobValue }}"
                    data-age-display="dob_age"
                    max="{{ now()->subYears(18)->toDateString() }}"
                    class="w-full bg-gray-50 border @error('dob') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                <p
                    id="dob_age"
                    class="mt-1.5 text-xs font-medium text-indigo-600"
                    data-age-template="{{ __('applicants.age_years', ['age' => ':age']) }}"
                    data-age-empty=""
                    @if(! $dobValue) hidden @endif
                >
                    @if($dobValue)
                        {{ __('applicants.age_years', ['age' => $ageYears ?? '—']) }}
                    @endif
                </p>
                @error('dob') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.phone_hint') }} @include('partials.required-mark')</label>
                @include('partials.inputs.phone-input', [
                    'name' => 'phone',
                    'value' => old('phone', $applicant?->phone ?? ''),
                    'readonly' => $lockRegistrationFields,
                    'class' => '',
                ])
                @error('phone') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.email') }} @include('partials.required-mark')</label>
                <input type="email" name="email" id="email" value="{{ old('email', $applicant?->email ?? '') }}" @readonly($lockRegistrationFields) class="w-full border @error('email') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $lockRegistrationFields ? $lockedInputClass : 'bg-gray-50' }}">
                @error('email') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    @endif

    @if($lockNidaFields)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
            <div>
                <label for="phone" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.phone_hint') }} @include('partials.required-mark')</label>
                @include('partials.inputs.phone-input', [
                    'name' => 'phone',
                    'value' => old('phone', $applicant?->phone ?? ''),
                    'readonly' => $lockRegistrationFields,
                    'class' => '',
                ])
                @error('phone') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.email') }} @include('partials.required-mark')</label>
                <input type="email" name="email" id="email" value="{{ old('email', $applicant?->email ?? '') }}" @readonly($lockRegistrationFields) class="w-full border @error('email') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $lockRegistrationFields ? $lockedInputClass : 'bg-gray-50' }}">
                @error('email') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    @endif
</div>
@endif

@if(! $selfServiceOnboarding && ! $lockNidaFields)
<div class="bg-white p-6 rounded-xl border border-gray-200 space-y-6">
    <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">{{ __('applicants.section_demographics') }}</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="sex" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.sex') }} @include('partials.required-mark')</label>
            @include('partials.inputs.female-sex-field', [
                'class' => 'w-full border rounded-lg px-4 py-2.5 text-sm '.($errors->has('sex') ? 'border-red-500' : 'border-gray-300'),
            ])
            @error('sex') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="nationality" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.nationality') }}</label>
            <input type="text" name="nationality" id="nationality" value="{{ old('nationality', $applicant?->nationality ?? 'Tanzanian') }}" class="w-full bg-gray-50 border @error('nationality') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('nationality') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
@endif

@if($selfServiceOnboarding)
<div x-show="step === 1" x-cloak>
@endif
<div class="bg-white p-6 rounded-xl border border-gray-200 space-y-6">
    <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">{{ __('applicants.section_loan_preference') }}</h2>

    @if($selfServiceOnboarding)
        <p class="text-sm text-slate-600">{{ __('applicants.onboarding_loan_type_hint') }}</p>
        <style>
            .loan-type-picker { display: grid; grid-template-columns: 1fr; gap: 1rem; }
            @media (min-width: 640px) { .loan-type-picker { grid-template-columns: 1fr 1fr; } }
            .loan-type-card {
                position: relative;
                display: flex;
                cursor: pointer;
                border-radius: 1rem;
                border: 2px solid #e2e8f0;
                padding: 1rem 1.1rem;
                background: #fff;
                transition: border-color .22s ease, box-shadow .22s ease, transform .22s ease;
                overflow: hidden;
            }
            .loan-type-card::before {
                content: '';
                position: absolute;
                inset: 0;
                opacity: 0;
                transition: opacity .22s ease;
                pointer-events: none;
            }
            .loan-type-card:hover {
                border-color: #93c5fd;
                box-shadow: 0 4px 14px rgba(37, 99, 235, 0.08);
            }
            .loan-type-card__body { position: relative; z-index: 1; }
            .loan-type-card__title { font-weight: 600; color: #0f172a; }
            .loan-type-card__hint { margin-top: .25rem; font-size: .875rem; color: #64748b; }
            .loan-type-card--individual:has(:checked) {
                border-color: #3b82f6;
                box-shadow: 0 8px 24px rgba(37, 99, 235, 0.14);
                transform: translateY(-1px);
            }
            .loan-type-card--individual:has(:checked)::before {
                opacity: 1;
                background: linear-gradient(135deg, rgba(37, 99, 235, 0.14) 0%, rgba(56, 189, 248, 0.1) 42%, rgba(255, 255, 255, 0) 72%);
            }
            .loan-type-card--individual:has(:checked) .loan-type-card__title { color: #1e3a8a; }
            .loan-type-card--group:hover {
                border-color: #93c5fd;
                box-shadow: 0 4px 14px rgba(37, 99, 235, 0.08);
            }
            .loan-type-card--group:has(:checked) {
                border-color: #3b82f6;
                box-shadow: 0 8px 24px rgba(37, 99, 235, 0.14);
                transform: translateY(-1px);
            }
            .loan-type-card--group:has(:checked)::before {
                opacity: 1;
                background: linear-gradient(135deg, rgba(37, 99, 235, 0.13) 0%, rgba(56, 189, 248, 0.11) 45%, rgba(186, 230, 253, 0.08) 72%, rgba(255, 255, 255, 0) 100%);
            }
            .loan-type-card--group:has(:checked) .loan-type-card__title { color: #1e3a8a; }
        </style>
        <div class="loan-type-picker">
            @foreach(Applicant::LOAN_TYPES as $type)
                <label class="loan-type-card loan-type-card--{{ $type }}">
                    <input
                        type="radio"
                        name="preferred_loan_type"
                        value="{{ $type }}"
                        class="sr-only"
                        required
                        x-model="loanType"
                        @checked($loanTypeValue === $type)
                    >
                    <div class="loan-type-card__body">
                        <p class="loan-type-card__title">{{ __('applicants.loan_types.'.$type) }}</p>
                        <p class="loan-type-card__hint">{{ __('applicants.loan_type_hints.'.$type) }}</p>
                    </div>
                </label>
            @endforeach
        </div>
        @error('preferred_loan_type') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="sm:col-span-2">
            <label for="preferred_loan_type" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.preferred_loan_type') }} @include('partials.required-mark')</label>
            <select name="preferred_loan_type" id="preferred_loan_type" required class="app-select @error('preferred_loan_type') app-select-error @enderror">
                <option value="">{{ __('applicants.select_loan_type') }}</option>
                @foreach(Applicant::LOAN_TYPES as $type)
                    <option value="{{ $type }}" @selected($loanTypeValue === $type)>{{ __('applicants.loan_types.'.$type) }}</option>
                @endforeach
            </select>
            @error('preferred_loan_type') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
    @endif
</div>
@if($selfServiceOnboarding)
</div>
@endif

@if($selfServiceOnboarding)
<div x-show="step === 2" x-cloak>
@endif
<div class="bg-white p-6 rounded-xl border border-gray-200 space-y-6">
    <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">{{ __('applicants.section_personal_status') }}</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="marital_status" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.marital_status') }} @include('partials.required-mark')</label>
            <select
                name="marital_status"
                id="marital_status"
                required
                class="app-select @error('marital_status') app-select-error @enderror"
                @if($selfServiceOnboarding) x-model="maritalStatus" @endif
            >
                <option value="">{{ __('applicants.select_marital_status') }}</option>
                @foreach(Applicant::MARITAL_STATUSES as $status)
                    <option value="{{ $status }}" @selected($maritalValue === $status)>{{ __('applicants.marital_statuses.'.$status) }}</option>
                @endforeach
                @if($maritalValue && ! in_array($maritalValue, Applicant::MARITAL_STATUSES, true))
                    <option value="{{ $maritalValue }}" selected>{{ $maritalValue }}</option>
                @endif
            </select>
            @error('marital_status') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="has_disability" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.has_disability') }} @include('partials.required-mark')</label>
            <select
                name="has_disability"
                id="has_disability"
                required
                class="app-select @error('has_disability') app-select-error @enderror"
                @if($selfServiceOnboarding) x-model="hasDisability" @endif
            >
                <option value="">{{ __('applicants.select_yes_no') }}</option>
                <option value="1" @selected((string) $disabilityValue === '1')>{{ __('common.yes') }}</option>
                <option value="0" @selected((string) $disabilityValue === '0')>{{ __('common.no') }}</option>
            </select>
            @error('has_disability') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
@if($selfServiceOnboarding)
</div>
@endif

@if($selfServiceOnboarding)
<div x-show="step === 3" x-cloak>
@endif
<div class="bg-white p-6 rounded-xl border border-gray-200 space-y-6">
    <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">{{ __('applicants.section_residential_address') }}</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="region_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.residential_region') }} @include('partials.required-mark')</label>
            <select id="region_select" name="region_id" required class="app-select">
                <option value="">-- {{ __('geo.select_region') }} --</option>
                @foreach($regions as $region)
                    <option value="{{ $region->id }}" @selected(old('region_id', $regionId ?? null) == $region->id)>{{ $region->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="district_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.district') }} @include('partials.required-mark')</label>
            <select id="district_select" class="app-select" disabled required>
                <option value="">-- {{ __('geo.select_district') }} --</option>
            </select>
        </div>

        <div>
            <label for="council_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.council') }} @include('partials.required-mark')</label>
            <select id="council_select" class="app-select" disabled required>
                <option value="">-- {{ __('geo.select_council') }} --</option>
            </select>
        </div>

        <div>
            <label for="ward_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.ward') }} @include('partials.required-mark')</label>
            <select id="ward_select" class="app-select" disabled required>
                <option value="">-- {{ __('geo.select_ward') }} --</option>
            </select>
        </div>

        <div class="sm:col-span-2">
            <label for="street_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.street') }} @include('partials.required-mark')</label>
            <select id="street_select" name="location_id" required class="app-select @error('location_id') app-select-error @enderror" disabled>
                <option value="">-- {{ __('geo.select_street') }} --</option>
            </select>
            @error('location_id') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="postal_code" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.postal_code') }}</label>
            <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $applicant?->postal_code ?? '') }}" maxlength="20" class="w-full bg-gray-50 border @error('postal_code') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('postal_code') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="po_box" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.po_box') }}</label>
            <input type="text" name="po_box" id="po_box" value="{{ old('po_box', $applicant?->po_box ?? '') }}" maxlength="50" class="w-full bg-gray-50 border @error('po_box') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('po_box') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
@if($selfServiceOnboarding)
</div>
@endif
