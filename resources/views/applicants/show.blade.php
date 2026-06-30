@extends('layouts.app')

@section('title', __('applicants.show_title'))

@section('content')
<div class="page page-medium">
    <div class="page-header">
        <a href="{{ route('applicants.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white transition-colors">&larr; {{ __('applicants.back_to_registry') }}</a>
        <div class="page-actions">
            <a href="{{ route('applicants.edit', $applicant) }}" class="app-btn bg-amber-600 text-white hover:bg-amber-500">{{ __('applicants.edit_profile') }}</a>
        </div>
    </div>

    <div class="app-card overflow-hidden">
        <div class="app-card-header sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">{{ $applicant->full_name }}</h1>
                <p class="page-subtitle font-mono text-xs">
                    {{ __('applicants.system_profile_id', ['id' => $applicant->id]) }}
                    &bull; {{ __('applicants.linked_user', ['name' => $applicant->user?->name ?? __('common.na')]) }}
                </p>
            </div>
            @include('partials.badge', ['variant' => 'light', 'text' => __('applicants.manual_mode'), 'class' => 'mt-2 sm:mt-0'])
        </div>

        <div class="app-card-padded space-y-8">
            <section>
                <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2 mb-5">{{ __('applicants.section_identification') }}</h2>
                <div class="detail-grid">
                    @include('partials.detail-field', ['label' => __('applicants.first_name'), 'value' => $applicant->first_name])
                    @include('partials.detail-field', ['label' => __('applicants.middle_name'), 'value' => $applicant->middle_name])
                    @include('partials.detail-field', ['label' => __('applicants.last_name'), 'value' => $applicant->last_name])
                    @include('partials.detail-field', ['label' => __('applicants.nin'), 'value' => $applicant->nin, 'mono' => true])
                    @include('partials.detail-field', ['label' => __('applicants.dob'), 'value' => $applicant->dob?->translatedFormat('M d, Y')])
                    @include('partials.detail-field', ['label' => __('applicants.phone_number'), 'value' => $applicant->phone])
                    @include('partials.detail-field', ['label' => __('applicants.email'), 'value' => $applicant->email])
                </div>
            </section>

            <section>
                <h2 class="text-sm font-semibold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2 mb-5">{{ __('applicants.section_demographics') }}</h2>
                <div class="detail-grid">
                    @include('partials.detail-field', ['label' => __('applicants.sex'), 'value' => $applicant->sex])
                    @include('partials.detail-field', ['label' => __('applicants.marital_status'), 'value' => $applicant->marital_status])
                    @include('partials.detail-field', ['label' => __('applicants.nationality'), 'value' => $applicant->nationality])
                    @include('partials.detail-field', ['label' => __('geo.region'), 'value' => $region?->name])
                    @include('partials.detail-field', ['label' => __('geo.district'), 'value' => $district?->name])
                    @include('partials.detail-field', ['label' => __('geo.council'), 'value' => $council?->name])
                    @include('partials.detail-field', ['label' => __('geo.ward'), 'value' => $ward?->name])
                    @include('partials.detail-field', ['label' => __('geo.street'), 'value' => $location?->name])
                </div>
            </section>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="app-card app-card-padded space-y-4">
            <h3 class="text-sm font-bold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2">{{ __('applicants.linked_groups') }}</h3>

            <ul class="divide-y divide-slate-100 dark:divide-white/10">
                @forelse($applicant->groups as $group)
                    <li class="py-3 flex items-center justify-between text-sm">
                        <span class="font-semibold text-slate-900 dark:text-white">{{ $group->name }}</span>
                        <form action="{{ route('applicants.detach-group', [$applicant, $group]) }}" method="POST" onsubmit="return confirm(@json(__('applicants.unlink_confirm')));">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-900 dark:text-red-400 font-medium">{{ __('applicants.remove') }}</button>
                        </form>
                    </li>
                @empty
                    <li class="py-4 text-center text-xs text-slate-400">{{ __('applicants.no_groups_linked') }}</li>
                @endforelse
            </ul>
        </div>

        <div class="app-card app-card-padded space-y-4">
            <h3 class="text-sm font-bold tracking-wide uppercase text-indigo-600 border-b border-slate-100 dark:border-white/10 pb-2">{{ __('applicants.link_group') }}</h3>

            <form action="{{ route('applicants.attach-group', $applicant) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="group_id" class="detail-field-label">{{ __('applicants.select_group') }}</label>
                    <select name="group_id" id="group_id" required class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-4 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">{{ __('applicants.select_group') }}</option>
                        @foreach($groups as $loanGroup)
                            <option value="{{ $loanGroup->hashid }}" @selected(old('group_id') === $loanGroup->hashid)>{{ $loanGroup->name }}</option>
                        @endforeach
                    </select>
                    @error('group_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="app-btn app-btn-primary w-full">
                    {{ __('applicants.link_group_button') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
