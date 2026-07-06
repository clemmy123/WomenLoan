@extends('layouts.app')

@section('title', __('groups.edit_title'))

@section('content')
<div class="page page-narrow">
    <div class="mb-8">
        <h2 class="text-xl font-bold text-slate-900">{{ __('groups.edit_title') }}</h2>
        <p class="text-sm text-slate-500">{{ __('groups.edit_subtitle', ['name' => $loanGroup->name]) }}</p>
    </div>

    <form action="{{ route('loan-groups.update', $loanGroup) }}" method="POST" class="bg-white p-6 rounded-2xl border border-slate-200/60  space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('groups.group_name') }} @include('partials.required-mark')</label>
                <input type="text" name="name" value="{{ old('name', $loanGroup->name) }}" class="w-full mt-1 p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none text-sm" required>
            </div>

            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('groups.reg_number') }}</label>
                <input type="text" name="registration_number" value="{{ old('registration_number', $loanGroup->registration_number) }}" class="w-full mt-1 p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>

            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('groups.phone_number') }}</label>
                <input type="text" name="phone" value="{{ old('phone', $loanGroup->phone) }}" class="w-full mt-1 p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>

            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('groups.email_address') }}</label>
                <input type="email" name="email" value="{{ old('email', $loanGroup->email) }}" class="w-full mt-1 p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>
        </div>

        <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('groups.group_members') }}</label>
            <div class="mt-2 h-64 overflow-y-auto border border-slate-200 rounded-xl p-4 bg-slate-50">
                @foreach($applicants as $applicant)
                <label class="flex items-center gap-3 p-2 hover:bg-white rounded-lg cursor-pointer transition-colors">
                    <input type="checkbox"
                           name="applicants[]"
                           value="{{ $applicant->id }}"
                           {{ $loanGroup->applicants->contains($applicant->id) ? 'checked' : '' }}
                           class="rounded text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-slate-700">{{ $applicant->full_name }}</span>
                    <span class="text-[10px] text-slate-400 ml-auto font-mono">{{ $applicant->nin }}</span>
                </label>
                @endforeach
            </div>
            <p class="text-[10px] text-slate-400 mt-2 italic">* {{ __('groups.members_checked_hint') }}</p>
        </div>

        <div class="flex items-center gap-4 pt-4 border-t border-slate-100">
            <a href="{{ route('loan-groups.show', $loanGroup) }}" class="app-btn app-btn-outline px-6 py-3 font-bold">{{ __('common.cancel') }}</a>
            <button type="submit" class="app-btn app-btn-primary flex-1 px-6 py-3 font-bold">{{ __('groups.update_group') }}</button>
        </div>
    </form>
</div>
@endsection
