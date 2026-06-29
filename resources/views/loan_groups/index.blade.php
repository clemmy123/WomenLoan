@extends('layouts.app')

@section('content')
<div class="page">
    @include('partials.page-header', [
        'title' => __('groups.index_title'),
        'subtitle' => __('groups.index_subtitle'),
        'actions' => '<a href="'.e(route('loan-groups.create')).'" class="app-btn app-btn-primary"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> '.e(__('groups.register_new')).'</a>',
    ])

    <div class="app-card app-card-padded">
        <form action="{{ route('loan-groups.index') }}" method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('groups.search_placeholder') }}" class="app-input flex-1">
            <button type="submit" class="app-btn app-btn-secondary">{{ __('common.search') }}</button>
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
                    </tr>
                </thead>
                <tbody>
                    @forelse($loanGroups as $group)
                    <tr>
                        <td>
                            <a href="{{ route('loan-groups.show', $group) }}" class="text-sm font-bold text-indigo-600 dark:text-indigo-400 hover:underline">{{ $group->name }}</a>
                            <p class="text-[11px] text-slate-400 dark:text-zinc-500">{{ $group->phone }}</p>
                        </td>
                        <td class="text-sm font-mono text-slate-600 dark:text-zinc-400">{{ $group->registration_number }}</td>
                        <td class="text-center">
                            @include('partials.badge', ['variant' => 'primary', 'text' => __('groups.members_label', ['count' => $group->applicants_count ?? 0])])
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="app-table-empty">{{ __('groups.no_groups') }}</td>
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
