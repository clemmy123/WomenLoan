@extends('layouts.app')

@section('title', __('applicants.title'))

@section('content')
<div class="page">
    @include('partials.page-header', [
        'title' => __('applicants.registry'),
        'subtitle' => __('applicants.registry_subtitle'),
        'actions' => '<a href="'.e(route('applicants.create')).'" class="app-btn app-btn-primary">'.e(__('applicants.add_new')).'</a>',
    ])

    <div class="app-card app-card-padded">
        <form action="{{ route('applicants.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center" x-ref="searchForm">
            <div class="relative flex-grow">
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="{{ __('applicants.search_please') }}"
                    class="app-input"
                    autocomplete="off"
                    @input.debounce.350ms="$refs.searchForm.requestSubmit()"
                >
            </div>
            @if(request('search'))
                <a href="{{ route('applicants.index') }}" class="bg-gray-100 text-gray-700 border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium hover:bg-gray-200 transition-all flex items-center justify-center shrink-0">{{ __('common.clear') }}</a>
            @endif
        </form>
    </div>

    <div class="app-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('common.full_name') }}</th>
                        <th>{{ __('applicants.nin') }}</th>
                        <th>{{ __('common.contact_info') }}</th>
                        <th>{{ __('common.metrics') }}</th>
                        <th class="text-right">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applicants as $applicant)
                        <tr>
                            <td>
                                <div class="font-semibold text-gray-900">{{ $applicant->full_name }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $applicant->sex ?? __('common.unspecified') }} &bull; {{ $applicant->marital_status ?? __('common.na') }}</div>
                            </td>
                            <td class="font-mono text-xs text-gray-600 tracking-wider">
                                {{ $applicant->nin }}
                            </td>
                            <td class="space-y-0.5">
                                <div class="text-gray-900 font-medium">{{ $applicant->phone }}</div>
                                <div class="text-xs text-gray-500">{{ $applicant->email }}</div>
                            </td>
                            <td class="space-x-2">
                                @include('partials.badge', ['variant' => 'primary', 'text' => __('common.loans_count', ['count' => $applicant->loans_count])])
                                @include('partials.badge', ['variant' => 'info', 'text' => __('common.groups_count', ['count' => $applicant->groups_count])])
                            </td>
                            <td class="text-right whitespace-nowrap">
                                @include('partials.applicant-row-actions', ['applicant' => $applicant])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="app-table-empty">
                                <div class="text-base font-medium">{{ __('applicants.no_matches') }}</div>
                                <div class="text-xs mt-1 opacity-70">{{ __('applicants.no_matches_hint') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($applicants->total() > 0)
            <div class="app-card-footer">
                {{ $applicants->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
