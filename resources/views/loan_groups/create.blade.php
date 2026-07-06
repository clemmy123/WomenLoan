@extends('layouts.app')

@section('title', __('groups.create_title'))

@section('content')
<div class="page page-medium">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900">{{ __('groups.create_title') }}</h2>
        <p class="text-sm text-slate-500">{{ __('groups.create_subtitle') }}</p>
    </div>

    <form action="{{ route('loan-groups.store') }}" method="POST" class="bg-white p-8 rounded-2xl border border-slate-200/60  space-y-8">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('groups.group_name') }} @include('partials.required-mark')</label>
                <input type="text" name="name" class="w-full mt-1 p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none text-sm" required>
            </div>
            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('groups.reg_number') }}</label>
                <input type="text" name="registration_number" class="w-full mt-1 p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>
        </div>

        <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('groups.select_members') }}</label>
            <div class="mt-2 h-64 overflow-y-auto border border-slate-200 rounded-xl p-4 bg-slate-50">
                @foreach($applicants as $applicant)
                <label class="flex items-center gap-3 p-3 hover:bg-white rounded-lg cursor-pointer transition-colors border-b border-slate-100 last:border-0">
                    <input type="checkbox" name="applicants[]" value="{{ $applicant->id }}" class="rounded text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-slate-700">{{ $applicant->full_name }}</span>
                    <span class="text-[10px] text-slate-400 ml-auto font-mono">{{ $applicant->nin }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit" class="app-btn app-btn-primary px-8 py-3 font-bold">
                {{ __('groups.save_group') }}
            </button>
        </div>
    </form>
</div>
@endsection
