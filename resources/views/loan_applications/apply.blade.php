@extends('layouts.app')

@section('title', 'Loan Application Wizard')

@section('content')
<div x-data="{
        step: {{ $startStep ?? 1 }},
        totalSteps: 7,
        selectedRegion: @json(old('region_id')),
        selectedDistrict: @json(old('district_id')),
        selectedCouncil: @json(old('council_id')),
        selectedWard: @json(old('ward_id')),
        selectedStreet: @json(old('street_id')),
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
                this.error = 'Failed to load data';
            } finally {
                this.loading = false;
            }
        },

        loadDistricts(regionId) {
            this.districts = []; this.councils = []; this.wards = []; this.streets = [];
            if (!regionId) return;
            this.fetchData('/loan-applications/districts/' + regionId, 'districts');
        },
        loadCouncils(districtId) {
            this.councils = []; this.wards = []; this.streets = [];
            if (!districtId) return;
            this.fetchData('/loan-applications/councils/' + districtId, 'councils');
        },
        loadWards(councilId) {
            this.wards = []; this.streets = [];
            if (!councilId) return;
            this.fetchData('/loan-applications/wards/' + councilId, 'wards');
        },
        loadStreets(wardId) {
            this.streets = [];
            if (!wardId) return;
            this.fetchData('/loan-applications/streets/' + wardId, 'streets');
        }
    }"
    x-init="
        if (selectedRegion) loadDistricts(selectedRegion);
        if (selectedDistrict) loadCouncils(selectedDistrict);
        if (selectedCouncil) loadWards(selectedCouncil);
        if (selectedWard) loadStreets(selectedWard);
    "
    class="max-w-4xl mx-auto space-y-6">

    {{-- Progress Header --}}
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <h2 class="text-xl font-bold text-slate-900">Loan Application</h2>
        <div class="flex items-center gap-2 mt-4">
            <template x-for="i in totalSteps" :key="i">
                <div class="h-2 flex-1 rounded-full transition-all duration-300" 
                     :class="step >= i ? 'bg-indigo-600' : 'bg-slate-200'"></div>
            </template>
        </div>
        <p class="text-xs uppercase font-bold text-slate-400 mt-2">Step <span x-text="step"></span> of 7</p>
    </div>

    {{-- Error Alert --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 p-4 rounded-2xl text-red-800 text-xs shadow-sm">
            <strong>Check the errors below:</strong>
            <ul class="list-disc pl-5 mt-2">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div x-show="error" class="bg-red-50 border border-red-200 p-4 rounded-2xl text-red-800 text-xs shadow-sm">
        <strong x-text="error"></strong>
    </div>

    <form action="{{ route('loan-applications.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="track_id" value="{{ $trackId ?? old('track_id') }}">

        {{-- Step 1: Loan Type --}}
        <div x-show="step === 1" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="font-bold text-lg mb-4">1. Loan Type</h3>
            <select name="loan_type" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                <option value="">-- Select Loan Type --</option>
                <option value="individual">Individual</option>
                <option value="group">Group</option>
            </select>
        </div>

        {{-- Step 2: Business Information --}}
        <div x-show="step === 2" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="font-bold text-lg mb-4">2. Business Information</h3>

            <select name="region_id" x-model="selectedRegion" 
                @change="selectedDistrict=''; selectedCouncil=''; selectedWard=''; selectedStreet=''; districts=[]; councils=[]; wards=[]; streets=[]; loadDistricts(selectedRegion);" 
                required class="w-full p-3 border rounded-xl mb-3 bg-slate-50">
                <option value="">-- Select Region --</option>
                @foreach($regions as $region)
                    <option value="{{ $region->id }}" @selected(old('region_id') == $region->id)>{{ $region->name }}</option>
                @endforeach
            </select>

            <select name="district_id" x-model="selectedDistrict" 
                @change="selectedCouncil=''; selectedWard=''; selectedStreet=''; councils=[]; wards=[]; streets=[]; loadCouncils(selectedDistrict);" 
                :disabled="!districts.length || loading" class="w-full p-3 border rounded-xl mb-3 bg-white">
                <option value="">-- Select District --</option>
                <template x-for="district in districts" :key="district.id">
                    <option :value="district.id" x-text="district.name"></option>
                </template>
            </select>

            <select name="council_id" x-model="selectedCouncil" 
                @change="selectedWard=''; selectedStreet=''; wards=[]; streets=[]; loadWards(selectedCouncil);" 
                :disabled="!councils.length || loading" class="w-full p-3 border rounded-xl mb-3 bg-white">
                <option value="">-- Select Council --</option>
                <template x-for="council in councils" :key="council.id">
                    <option :value="council.id" x-text="council.name"></option>
                </template>
            </select>

            <select name="ward_id" x-model="selectedWard" 
                @change="selectedStreet=''; streets=[]; loadStreets(selectedWard);" 
                :disabled="!wards.length || loading" class="w-full p-3 border rounded-xl mb-3 bg-white">
                <option value="">-- Select Ward --</option>
                <template x-for="ward in wards" :key="ward.id">
                    <option :value="ward.id" x-text="ward.name"></option>
                </template>
            </select>

            <select name="street_id" x-model="selectedStreet" 
                :disabled="!streets.length || loading" class="w-full p-3 border rounded-xl mb-3 bg-white">
                <option value="">-- Select Street --</option>
                <template x-for="street in streets" :key="street.id">
                    <option :value="street.id" x-text="street.name"></option>
                </template>
            </select>

            {{-- <!-- Loading Indicator -->
            <div x-show="loading" class="text-sm text-indigo-600">Loading data...</div>

            <!-- Preview -->
            <div class="mt-4 text-sm text-slate-600">
                <p><strong>Region:</strong> <span x-text="selectedRegion"></span></p>
                <p><strong>District:</strong> <span x-text="selectedDistrict"></span></p>
                <p><strong>Council:</strong> <span x-text="selectedCouncil"></span></p>
                <p><strong>Ward:</strong> <span x-text="selectedWard"></span></p>
                <p><strong>Street:</strong> <span x-text="selectedStreet"></span></p>
            </div> --}}

            <input type="text" name="business_name" placeholder="Business Name" value="{{ old('business_name') }}"
                   class="w-full p-3 border rounded-xl mb-3" required>

            <input type="text" name="business_phone" placeholder="Business Phone" value="{{ old('business_phone') }}"
                   class="w-full p-3 border rounded-xl mb-3" required>

            <input type="email" name="business_email" placeholder="Business Email" value="{{ old('business_email') }}"
                   class="w-full p-3 border rounded-xl mb-3" required>

            <input type="text" name="business_sector" placeholder="Business Sector" value="{{ old('business_sector') }}"
                   class="w-full p-3 border rounded-xl mb-3" required>

            <input type="text" name="business_type" placeholder="Business Type" value="{{ old('business_type') }}"
                   class="w-full p-3 border rounded-xl mb-3" required>

            <input type="text" name="tin_number" placeholder="TIN Number/Business Registration Number" value="{{ old('tin_number') }}"
                   class="w-full p-3 border rounded-xl mb-3" required>

            <label class="block mb-2 font-semibold">Business Proposal Document <span class="text-red-500">*</span></label>
            <input type="file" name="business_proposal_document" required class="w-full p-3 border rounded-xl bg-slate-50 mb-3">

            <label class="block mb-2 font-semibold">Business Registration Attachment</label>
            <input type="file" name="business_registration_attachment" class="w-full p-3 border rounded-xl bg-slate-50">
        </div>

        {{-- Step 3: Applicant Information --}}
        <div x-show="step === 3" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="font-bold text-lg mb-4">3. Applicant Information</h3>
            <input type="text" name="applicant_name" placeholder="Full Name" class="w-full p-3 border rounded-xl mb-3">
            <input type="text" name="applicant_phone" placeholder="Phone Number" class="w-full p-3 border rounded-xl mb-3">
            <input type="text" name="applicant_nin" placeholder="NIN" class="w-full p-3 border rounded-xl">
        </div>

        {{-- Step 4: Guarantor Information --}}
        <div x-show="step === 4" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="font-bold text-lg mb-4">4. Guarantor Information</h3>
            <input type="text" name="guarantor_name" placeholder="Full Name" class="w-full p-3 border rounded-xl mb-3">
            <input type="text" name="guarantor_phone" placeholder="Phone Number" class="w-full p-3 border rounded-xl mb-3">
            <input type="text" name="guarantor_nin" placeholder="NIN" class="w-full p-3 border rounded-xl">
        </div>

        {{-- Step 5: Loan Amount --}}
        <div x-show="step === 5" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="font-bold text-lg mb-4">5. Loan Amount</h3>
            <input type="number" name="requested_amount" placeholder="Requested Amount" required class="w-full p-3 border rounded-xl">
        </div>

        {{-- Step 6: Bank Details --}}
        <div x-show="step === 6" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="font-bold text-lg mb-4">6. Bank Details</h3>
            <input type="text" name="bank_name" placeholder="Bank Name" class="w-full p-3 border rounded-xl mb-3">
            <input type="text" name="bank_number" placeholder="Account Number" class="w-full p-3 border rounded-xl">
        </div>

        {{-- Step 7: Declaration --}}
        <div x-show="step === 7" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="font-bold text-lg mb-4">7. Declaration</h3>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="declaration" value="1" required class="w-5 h-5 accent-indigo-600"> 
                <span>I confirm that all information provided is accurate and true.</span>
            </label>
        </div>

        {{-- Navigation --}}
        <div class="flex justify-between items-center mt-8 p-4 bg-white rounded-2xl border border-slate-200 shadow-sm">
            <button type="button" x-show="step > 1" @click="step--" class="px-6 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl font-bold text-slate-600">Back</button>
            <div class="flex gap-3 ml-auto">
                <button type="submit" name="form_action" value="save_draft" class="px-6 py-2 text-indigo-600 font-bold hover:bg-indigo-50 rounded-xl">Save Draft</button>
                <button type="button" x-show="step < totalSteps" @click="step++" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold">Next</button>
                <button type="submit" x-show="step === totalSteps" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold">Submit Application</button>
            </div>
        </div>
    </form>
</div>
@endsection
