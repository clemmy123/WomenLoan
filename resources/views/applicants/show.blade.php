@extends('layouts.app')

@section('title', __('applicants.show_title'))

@section('content')
<div class="page page-medium">
    <div class="flex items-center justify-between">
        <a href="{{ route('applicants.index') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-900 transition-colors">&larr; {{ __('applicants.back_to_registry') }}</a>
        <a href="{{ route('applicants.edit', $applicant) }}" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white  hover:bg-amber-500 transition-all">{{ __('applicants.edit_profile') }}</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200  overflow-hidden">
        <div class="p-6 bg-gray-50 border-b border-gray-200 sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ $applicant->full_name }}</h1>
                <p class="text-xs text-gray-500 font-mono mt-1">
                    {{ __('applicants.system_profile_id', ['id' => $applicant->id]) }}
                    &bull; {{ __('applicants.assigned_admin', ['user' => $applicant->user_id]) }}
                </p>
            </div>
            @include('partials.badge', ['variant' => 'light', 'text' => __('applicants.manual_mode'), 'class' => 'mt-2 sm:mt-0'])
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
            <div>
                <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">{{ __('applicants.nin') }}</span>
                <span class="font-mono text-base tracking-wide text-gray-900">{{ $applicant->nin }}</span>
            </div>
            <div>
                <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">{{ __('applicants.dob') }}</span>
                <span class="text-base text-gray-900">{{ \Carbon\Carbon::parse($applicant->dob)->translatedFormat('M d, Y') }}</span>
            </div>
            <div>
                <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">{{ __('applicants.phone_number') }}</span>
                <span class="text-base font-semibold text-gray-900">{{ $applicant->phone }}</span>
            </div>
            <div>
                <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">{{ __('applicants.email') }}</span>
                <span class="text-base text-gray-900">{{ $applicant->email }}</span>
            </div>
            <div>
                <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">{{ __('applicants.demographics_label') }}</span>
                <span class="text-base text-gray-900">{{ $applicant->sex ?? __('common.unspecified') }} &bull; {{ $applicant->marital_status ?? __('common.na') }}</span>
            </div>
            <div>
                <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">{{ __('applicants.location_nationality') }}</span>
                <span class="text-base text-gray-900">{{ $applicant->location->name ?? __('applicants.none_assigned') }} ({{ $applicant->nationality }})</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-xl border border-gray-200  space-y-4">
            <h3 class="text-sm font-bold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">{{ __('applicants.linked_groups') }}</h3>

            <ul class="divide-y divide-gray-100">
                @forelse($applicant->groups as $group)
                    <li class="py-3 flex items-center justify-between text-sm">
                        <span class="font-semibold text-gray-900">{{ $group->name }}</span>
                        <form action="{{ route('applicants.detach-group', [$applicant, $group]) }}" method="POST" onsubmit="return confirm(@json(__('applicants.unlink_confirm')));">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-900 font-medium">{{ __('applicants.remove') }}</button>
                        </form>
                    </li>
                @empty
                    <li class="py-4 text-center text-xs text-gray-400">{{ __('applicants.no_groups_linked') }}</li>
                @endforelse
            </ul>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200  space-y-4">
            <h3 class="text-sm font-bold tracking-wide uppercase text-indigo-600 border-b border-gray-100 pb-2">{{ __('applicants.link_group') }}</h3>

            <form action="{{ route('applicants.attach-group', $applicant) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="group_id" class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">{{ __('applicants.select_group') }}</label>
                    <select name="group_id" id="group_id" required class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">{{ __('applicants.select_group') }}</option>
                        @foreach($groups as $loanGroup)
                            <option value="{{ $loanGroup->hashid }}" @selected(old('group_id') === $loanGroup->hashid)>{{ $loanGroup->name }}</option>
                        @endforeach
                    </select>
                    @error('group_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="w-full bg-gray-900 text-white rounded-lg py-2 text-sm font-semibold hover:bg-gray-800 transition-all">
                    {{ __('applicants.link_group_button') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
