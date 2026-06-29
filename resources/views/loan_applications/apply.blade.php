@extends('layouts.app')

@section('title', __('loans.wizard_title'))

@section('content')
<div x-data="{
        step: {{ $startStep ?? 1 }},
        totalSteps: 7,
        selectedRegion: @json(old('region_id')),
        selectedDistrict: @json(old('district_id')),
        selectedCouncil: @json(old('council_id')),
        selectedWard: @json(old('ward_id')),
        selectedStreet: @json(old('street_id')),
        geoApi: @json($geoApi),
        i18n: @json([
            'load_failed' => __('loans.load_failed'),
            'loading' => __('loans.loading_data'),
            'step' => __('common.step_n_of', ['step' => ':step', 'total' => 7]),
        ]),
        districts: [],
        councils: [],
        wards: [],
        streets: [],
        loading: false,
        error: null,

        async fetchData(url, target) {
            this.loading = true;
            this.error = null;
            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }
                const data = await response.json();
                this[target] = data?.data ?? data;
            } catch (e) {
                console.error(e);
                this.error = this.i18n.load_failed;
            } finally {
                this.loading = false;
            }
        },

        loadDistricts(regionId) {
            this.districts = []; this.councils = []; this.wards = []; this.streets = [];
            if (!regionId) return;
            this.fetchData(this.geoApi.districts + '/' + regionId, 'districts');
        },
        loadCouncils(districtId) {
            this.councils = []; this.wards = []; this.streets = [];
            if (!districtId) return;
            this.fetchData(this.geoApi.councils + '/' + districtId, 'councils');
        },
        loadWards(councilId) {
            this.wards = []; this.streets = [];
            if (!councilId) return;
            this.fetchData(this.geoApi.wards + '/' + councilId, 'wards');
        },
        loadStreets(wardId) {
            this.streets = [];
            if (!wardId) return;
            this.fetchData(this.geoApi.streets + '/' + wardId, 'streets');
        }
    }"
    x-init="
        if (selectedRegion) loadDistricts(selectedRegion);
        if (selectedDistrict) loadCouncils(selectedDistrict);
        if (selectedCouncil) loadWards(selectedCouncil);
        if (selectedWard) loadStreets(selectedWard);
    "
    class="page page-medium">

    <div class="bg-white p-6 rounded-2xl border border-slate-200">
        <h2 class="text-xl font-bold text-slate-900">{{ __('loans.apply_title') }}</h2>
        <div class="flex items-center gap-2 mt-4">
            <template x-for="i in totalSteps" :key="i">
                <div class="h-2 flex-1 rounded-full transition-all duration-300"
                     :class="step >= i ? 'bg-indigo-600' : 'bg-slate-200'"></div>
            </template>
        </div>
        <p class="text-xs uppercase font-bold text-slate-400 mt-2" x-text="i18n.step.replace(':step', step)"></p>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 p-4 rounded-2xl text-red-800 text-xs">
            <strong>{{ __('common.check_errors') }}</strong>
            <ul class="list-disc pl-5 mt-2">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div x-show="error" class="bg-red-50 border border-red-200 p-4 rounded-2xl text-red-800 text-xs">
        <strong x-text="error"></strong>
    </div>

    <div x-show="loading" class="text-sm text-indigo-600" x-text="i18n.loading"></div>

    <form action="{{ route('loan-applications.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="track_id" value="{{ $trackId ?? old('track_id') }}">

        <div x-show="step === 1" class="bg-white p-6 rounded-2xl border border-slate-200">
            <h3 class="font-bold text-lg mb-4">1. {{ __('loans.wizard_steps.1') }}</h3>
            <select name="loan_type" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                <option value="">-- {{ __('loans.select_loan_type') }} --</option>
                <option value="individual">{{ __('loans.types.individual') }}</option>
                <option value="group">{{ __('loans.types.group') }}</option>
            </select>
        </div>

        <div x-show="step === 2" class="bg-white p-6 rounded-2xl border border-slate-200">
            <h3 class="font-bold text-lg mb-4">2. {{ __('loans.wizard_steps.2') }}</h3>

            <select name="region_id" x-model="selectedRegion"
                @change="selectedDistrict=''; selectedCouncil=''; selectedWard=''; selectedStreet=''; districts=[]; councils=[]; wards=[]; streets=[]; loadDistricts(selectedRegion);"
                required class="w-full p-3 border rounded-xl mb-3 bg-slate-50">
                <option value="">-- {{ __('geo.select_region') }} --</option>
                @foreach($regions as $region)
                    <option value="{{ $region->id }}" @selected(old('region_id') == $region->id)>{{ $region->name }}</option>
                @endforeach
            </select>

            <select name="district_id" x-model="selectedDistrict"
                @change="selectedCouncil=''; selectedWard=''; selectedStreet=''; councils=[]; wards=[]; streets=[]; loadCouncils(selectedDistrict);"
                :disabled="!districts.length || loading" class="w-full p-3 border rounded-xl mb-3 bg-white">
                <option value="">-- {{ __('geo.select_district') }} --</option>
                <template x-for="district in districts" :key="district.id">
                    <option :value="district.id" x-text="district.name"></option>
                </template>
            </select>

            <select name="council_id" x-model="selectedCouncil"
                @change="selectedWard=''; selectedStreet=''; wards=[]; streets=[]; loadWards(selectedCouncil);"
                :disabled="!councils.length || loading" class="w-full p-3 border rounded-xl mb-3 bg-white">
                <option value="">-- {{ __('geo.select_council') }} --</option>
                <template x-for="council in councils" :key="council.id">
                    <option :value="council.id" x-text="council.name"></option>
                </template>
            </select>

            <select name="ward_id" x-model="selectedWard"
                @change="selectedStreet=''; streets=[]; loadStreets(selectedWard);"
                :disabled="!wards.length || loading" class="w-full p-3 border rounded-xl mb-3 bg-white">
                <option value="">-- {{ __('geo.select_ward') }} --</option>
                <template x-for="ward in wards" :key="ward.id">
                    <option :value="ward.id" x-text="ward.name"></option>
                </template>
            </select>

            <select name="street_id" x-model="selectedStreet"
                :disabled="!streets.length || loading" class="w-full p-3 border rounded-xl mb-3 bg-white">
                <option value="">-- {{ __('geo.select_street') }} --</option>
                <template x-for="street in streets" :key="street.id">
                    <option :value="street.id" x-text="street.name"></option>
                </template>
            </select>

            <input type="text" name="business_name" placeholder="{{ __('loans.business_name') }}" value="{{ old('business_name') }}"
                   class="w-full p-3 border rounded-xl mb-3" required>

            <input type="text" name="business_phone" placeholder="{{ __('loans.business_phone') }}" value="{{ old('business_phone') }}"
                   class="w-full p-3 border rounded-xl mb-3" required>

            <input type="email" name="business_email" placeholder="{{ __('loans.business_email') }}" value="{{ old('business_email') }}"
                   class="w-full p-3 border rounded-xl mb-3" required>

            <input type="text" name="business_sector" placeholder="{{ __('loans.business_sector') }}" value="{{ old('business_sector') }}"
                   class="w-full p-3 border rounded-xl mb-3" required>

            <input type="text" name="business_type" placeholder="{{ __('loans.business_type') }}" value="{{ old('business_type') }}"
                   class="w-full p-3 border rounded-xl mb-3" required>

            <input type="text" name="tin_number" placeholder="{{ __('loans.tin_number') }}" value="{{ old('tin_number') }}"
                   class="w-full p-3 border rounded-xl mb-3" required>

            <label class="block mb-2 font-semibold">{{ __('loans.business_proposal') }} <span class="text-red-500">*</span></label>
            <input type="file" name="business_proposal_document" required class="w-full p-3 border rounded-xl bg-slate-50 mb-3">

            <label class="block mb-2 font-semibold">{{ __('loans.business_registration') }}</label>
            <input type="file" name="business_registration_attachment" class="w-full p-3 border rounded-xl bg-slate-50">
        </div>

        <div x-show="step === 3" class="bg-white p-6 rounded-2xl border border-slate-200">
            <h3 class="font-bold text-lg mb-4">3. {{ __('loans.wizard_steps.3') }}</h3>
            <input type="text" name="applicant_name" placeholder="{{ __('loans.applicant_name') }}" class="w-full p-3 border rounded-xl mb-3">
            <input type="text" name="applicant_phone" placeholder="{{ __('loans.applicant_phone') }}" class="w-full p-3 border rounded-xl mb-3">
            <input type="text" name="applicant_nin" placeholder="{{ __('loans.applicant_nin') }}" class="w-full p-3 border rounded-xl">
        </div>

        <div x-show="step === 4" class="bg-white p-6 rounded-2xl border border-slate-200">
            <h3 class="font-bold text-lg mb-4">4. {{ __('loans.wizard_steps.4') }}</h3>
            <input type="text" name="guarantor_name" placeholder="{{ __('loans.guarantor_name') }}" class="w-full p-3 border rounded-xl mb-3">
            <input type="text" name="guarantor_phone" placeholder="{{ __('loans.guarantor_phone') }}" class="w-full p-3 border rounded-xl mb-3">
            <input type="text" name="guarantor_nin" placeholder="{{ __('loans.guarantor_nin') }}" class="w-full p-3 border rounded-xl">
        </div>

        <div x-show="step === 5" class="bg-white p-6 rounded-2xl border border-slate-200">
            <h3 class="font-bold text-lg mb-4">5. {{ __('loans.wizard_steps.5') }}</h3>
            <input type="number" name="requested_amount" placeholder="{{ __('loans.requested_amount') }}" required class="w-full p-3 border rounded-xl">
        </div>

        <div x-show="step === 6" class="bg-white p-6 rounded-2xl border border-slate-200">
            <h3 class="font-bold text-lg mb-4">6. {{ __('loans.wizard_steps.6') }}</h3>
            <input type="text" name="bank_name" placeholder="{{ __('loans.bank_name') }}" class="w-full p-3 border rounded-xl mb-3">
            <input type="text" name="bank_number" placeholder="{{ __('loans.bank_number') }}" class="w-full p-3 border rounded-xl">
        </div>

        <div x-show="step === 7" class="bg-white p-6 rounded-2xl border border-slate-200">
            <h3 class="font-bold text-lg mb-4">7. {{ __('loans.wizard_steps.7') }}</h3>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="declaration" value="1" required class="w-5 h-5 accent-indigo-600">
                <span>{{ __('loans.confirm_accuracy') }}</span>
            </label>
        </div>

        <div class="flex justify-between items-center mt-8 p-4 bg-white rounded-2xl border border-slate-200">
            <button type="button" x-show="step > 1" @click="step--" class="px-6 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl font-bold text-slate-600">{{ __('common.back') }}</button>
            <div class="flex gap-3 ml-auto">
                <button type="submit" name="form_action" value="save_draft" class="px-6 py-2 text-indigo-600 font-bold hover:bg-indigo-50 rounded-xl">{{ __('loans.save_draft') }}</button>
                <button type="button" x-show="step < totalSteps" @click="step++" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold">{{ __('common.next') }}</button>
                <button type="submit" x-show="step === totalSteps" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold">{{ __('loans.submit_application') }}</button>
            </div>
        </div>
    </form>
</div>
@endsection
