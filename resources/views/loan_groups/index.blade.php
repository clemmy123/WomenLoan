@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('groups.index_title') }}</h2>
            <p class="text-sm text-slate-500">{{ __('groups.index_subtitle') }}</p>
        </div>
        <a href="{{ route('loan-groups.create') }}" class="inline-flex items-center justify-center gap-2 bg-indigo-600 text-white px-5 py-2.5 rounded-2xl text-sm font-bold  hover:bg-indigo-700 transition-all">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('groups.register_new') }}
        </a>
    </div>

    <div class="app-card app-card-padded">
        <form action="{{ route('loan-groups.index') }}" method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('groups.search_placeholder') }}" class="app-input flex-1">
            <button type="submit" class="bg-slate-900 text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-slate-800 transition-all">{{ __('common.search') }}</button>
        </form>
    </div>

    <div class="app-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('groups.group_name') }}</th>
                        <th>{{ __('groups.reg_number') }}</th>
                        <th class="text-center">{{ __('common.members') }}</th>
                        <th class="text-right">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loanGroups as $group)
                    <tr>
                        <td>
                            <p class="text-sm font-bold text-slate-900">{{ $group->name }}</p>
                            <p class="text-[11px] text-slate-400">{{ $group->phone }}</p>
                        </td>
                        <td class="text-sm font-mono text-slate-600">{{ $group->registration_number }}</td>
                        <td class="text-center">
                            @include('partials.badge', ['variant' => 'primary', 'text' => __('groups.members_label', ['count' => $group->applicants_count ?? 0])])
                        </td>
                        <td class="text-right">
                            <div class="inline-flex items-center justify-end">
                                @include('partials.table-icon', ['action' => 'view', 'href' => route('loan-groups.show', $group), 'label' => __('groups.view_details')])
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="app-table-empty">{{ __('groups.no_groups') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($loanGroups->hasPages())
        <div class="app-card-footer">
            {{ $loanGroups->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
