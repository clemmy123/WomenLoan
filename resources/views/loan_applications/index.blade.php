@extends('layouts.app')

@section('title', __('loans.my_applications'))

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="app-card app-card-padded">
        <h2 class="text-xl font-bold text-slate-900">{{ __('loans.title') }}</h2>
        <p class="text-slate-500 mt-1">{{ __('loans.apply_subtitle') }}</p>
    </div>

    <div class="app-card overflow-hidden">
        <div class="app-card-header">
            <h3 class="font-bold text-slate-900">{{ __('loans.submitted') }}</h3>
        </div>
        @if($loans->count())
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('dashboard.track_id') }}</th>
                        <th>{{ __('common.type') }}</th>
                        <th>{{ __('dashboard.amount') }}</th>
                        <th>{{ __('dashboard.status') }}</th>
                        <th>{{ __('common.submitted') }}</th>
                        <th class="text-right">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loans as $loan)
                        <tr>
                            <td class="font-mono text-xs font-semibold text-indigo-600">{{ $loan->loan_track_id }}</td>
                            <td>{{ loan_type_label($loan->loan_type) }}</td>
                            <td>{{ format_tzs($loan->requested_amount) }}</td>
                            <td>
                                <div class="flex flex-wrap items-center gap-1">
                                    @include('partials.badge', ['variant' => 'secondary', 'text' => __('common.step_n_of', ['step' => $loan->current_step, 'total' => 9])])
                                    @include('partials.loan-status-badge', ['status' => $loan->status])
                                </div>
                            </td>
                            <td>{{ $loan->created_at->translatedFormat('d M Y') }}</td>
                            <td class="text-right">
                                <div class="inline-flex items-center justify-end">
                                    @include('partials.table-icon', ['action' => 'view', 'href' => route('loan-applications.show', $loan), 'label' => __('common.view')])
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="app-card-footer">{{ $loans->links() }}</div>
        @else
        <p class="app-table-empty">{{ __('loans.no_applications') }}</p>
        @endif
    </div>

    <div class="app-card app-card-padded">
        <h3 class="font-bold text-lg mb-4">{{ __('loans.drafts') }}</h3>
        @if($drafts->count())
            <ul class="space-y-3">
                @foreach($drafts as $draft)
                    <li class="flex justify-between items-center border p-3 rounded-xl">
                        <div>
                            <p class="font-bold text-slate-800">{{ __('dashboard.track_id') }}: {{ $draft->track_id }}</p>
                            <p class="text-slate-500 text-sm">{{ __('loans.saved_on', ['date' => $draft->updated_at->translatedFormat('d M Y, H:i')]) }}</p>
                        </div>
                        <a href="{{ route('loan-applications.create', ['resume_track_id' => $draft->track_id]) }}"
                           class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 text-sm font-bold">
                            {{ __('loans.resume') }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-slate-500">{{ __('loans.no_drafts') }}</p>
        @endif
    </div>

    <div class="text-center">
        <a href="{{ route('loan-applications.create') }}"
           class="px-6 py-3 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700">
            {{ __('loans.start_new') }}
        </a>
    </div>
</div>
@endsection
