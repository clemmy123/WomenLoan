@php
    use App\Models\Applicant;

    $isEdit = $applicant?->exists ?? false;
    $lockRegistrationFields = $lockRegistrationFields ?? false;
    $lockedInputClass = 'bg-gray-100 border-gray-200 text-gray-600 cursor-not-allowed focus:ring-0 focus:border-gray-200';
    $dobValue = old('dob', $isEdit && $applicant->dob ? $applicant->dob->format('Y-m-d') : '');
    $maritalValue = old('marital_status', $applicant?->marital_status ?? '');
@endphp

<div class="bg-white p-6 rounded-xl border border-gray-200 space-y-6">
    <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">{{ __('applicants.section_identification') }}</h2>

    @if($lockRegistrationFields)
        <p class="text-xs text-gray-500">{{ __('applicants.registration_fields_locked') }}</p>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div>
            <label for="first_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.first_name') }}</label>
            <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $applicant?->first_name ?? '') }}" @readonly($lockRegistrationFields) class="w-full border @error('first_name') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $lockRegistrationFields ? $lockedInputClass : 'bg-gray-50' }}">
            @error('first_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="middle_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.middle_name') }}</label>
            <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name', $applicant?->middle_name ?? '') }}" @readonly($lockRegistrationFields) class="w-full border @error('middle_name') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $lockRegistrationFields ? $lockedInputClass : 'bg-gray-50' }}">
            @error('middle_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="last_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.last_name') }}</label>
            <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $applicant?->last_name ?? '') }}" @readonly($lockRegistrationFields) class="w-full border @error('last_name') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $lockRegistrationFields ? $lockedInputClass : 'bg-gray-50' }}">
            @error('last_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
        <div>
            <label for="nin" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.nin') }}</label>
            <input type="text" name="nin" id="nin" value="{{ old('nin', $applicant?->nin ?? '') }}" class="w-full bg-gray-50 border @error('nin') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm font-mono tracking-widest text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('nin') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="dob" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.dob') }}</label>
            <input type="date" name="dob" id="dob" value="{{ $dobValue }}" class="w-full bg-gray-50 border @error('dob') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('dob') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="phone" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.phone_hint') }}</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone', $applicant?->phone ?? '') }}" placeholder="0712345678" @readonly($lockRegistrationFields) class="w-full border @error('phone') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $lockRegistrationFields ? $lockedInputClass : 'bg-gray-50' }}">
            @error('phone') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.email') }}</label>
            <input type="email" name="email" id="email" value="{{ old('email', $applicant?->email ?? '') }}" @readonly($lockRegistrationFields) class="w-full border @error('email') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $lockRegistrationFields ? $lockedInputClass : 'bg-gray-50' }}">
            @error('email') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-xl border border-gray-200 space-y-6">
    <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">{{ __('applicants.section_demographics') }}</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="sex" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.sex') }}</label>
            <select name="sex" id="sex" class="w-full bg-gray-50 border @error('sex') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">{{ __('applicants.select_sex') }}</option>
                <option value="Male" @selected(old('sex', $applicant?->sex ?? '') === 'Male')>{{ __('applicants.male') }}</option>
                <option value="Female" @selected(old('sex', $applicant?->sex ?? '') === 'Female')>{{ __('applicants.female') }}</option>
            </select>
            @error('sex') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="marital_status" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.marital_status') }}</label>
            <select name="marital_status" id="marital_status" class="w-full bg-gray-50 border @error('marital_status') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
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
            <label for="nationality" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('applicants.nationality') }}</label>
            <input type="text" name="nationality" id="nationality" value="{{ old('nationality', $applicant?->nationality ?? 'Tanzanian') }}" class="w-full bg-gray-50 border @error('nationality') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('nationality') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="region_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.residential_region') }}</label>
            <select id="region_select" name="region_id" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">-- {{ __('geo.select_region') }} --</option>
                @foreach($regions as $region)
                    <option value="{{ $region->id }}" @selected(old('region_id', $regionId ?? null) == $region->id)>{{ $region->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="district_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.district') }}</label>
            <select id="district_select" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500" disabled>
                <option value="">-- {{ __('geo.select_district') }} --</option>
            </select>
        </div>

        <div>
            <label for="council_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.council') }}</label>
            <select id="council_select" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500" disabled>
                <option value="">-- {{ __('geo.select_council') }} --</option>
            </select>
        </div>

        <div>
            <label for="ward_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.ward') }}</label>
            <select id="ward_select" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500" disabled>
                <option value="">-- {{ __('geo.select_ward') }} --</option>
            </select>
        </div>

        <div class="sm:col-span-2">
            <label for="street_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ __('geo.street') }}</label>
            <select id="street_select" name="location_id" class="w-full bg-gray-50 border @error('location_id') border-red-500 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500" disabled>
                <option value="">-- {{ __('geo.select_street') }} --</option>
            </select>
            @error('location_id') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
