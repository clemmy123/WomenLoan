@extends('layouts.app')

@section('title', __('groups.my_group'))

@section('content')
@php
    $membersPayload = $group->members->map(fn ($m) => [
        'id' => $m->id,
        'first_name' => $m->first_name,
        'middle_name' => $m->middle_name,
        'last_name' => $m->last_name,
        'full_name' => $m->full_name,
        'nin' => $m->nin,
        'dob' => $m->dob?->format('Y-m-d'),
        'dob_label' => $m->dob?->translatedFormat('d M Y'),
        'sex' => $m->sex,
        'marital_status' => $m->marital_status,
        'marital_status_label' => $m->marital_status ? __('applicants.marital_statuses.'.$m->marital_status) : __('common.na'),
        'phone' => $m->phone,
        'email' => $m->email,
        'is_group_leader' => (bool) $m->is_group_leader,
        'leadership_role' => $m->leadership_role,
        'leadership_role_label' => $m->leadershipRoleLabel(),
        'role_label' => $m->is_group_leader ? __('groups.group_leader') : __('groups.member'),
        'update_url' => route('my-group.members.update', $m),
        'destroy_url' => route('my-group.members.destroy', $m),
    ])->values();
@endphp
<script type="application/json" id="group-members-data">@json($membersPayload)</script>
<div
    class="app-page app-page-medium"
    data-group-show
    data-age-template="{{ __('applicants.age_years', ['age' => ':age']) }}"
>
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">{{ $group->name }}</h1>
            <p class="app-page-subtitle">{{ __('groups.my_group_subtitle') }}</p>
        </div>
        <div class="app-page-actions flex flex-wrap gap-2">
            @if($canManage)
                <button type="button" class="app-btn app-btn-secondary" data-open-modal="app-modal-add">{{ __('groups.add_member') }}</button>
            @endif
            @if($canStartApplication ?? false)
            <a href="{{ route('loan-applications.create') }}" class="app-btn app-btn-success">{{ __('loans.continue_as_group') }}</a>
            @endif
        </div>
    </div>

    <div class="app-card app-card-padded mb-6">
        <div class="detail-grid">
            @include('partials.detail-field', ['label' => __('groups.reg_number'), 'value' => $group->registration_number])
            @include('partials.detail-field', ['label' => __('groups.phone_number'), 'value' => $group->phone])
            @include('partials.detail-field', ['label' => __('groups.email_address'), 'value' => $group->email])
            @include('partials.detail-field', ['label' => __('groups.registered_on'), 'value' => $group->setup_completed_at ? format_app_datetime($group->setup_completed_at) : null])
        </div>
    </div>

    <div class="app-card overflow-hidden">
        <div class="app-card-header flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white">{{ __('groups.group_members') }}</h3>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">{{ __('groups.members_count', ['count' => $group->members->count()]) }}</p>
            </div>
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="app-table min-w-[720px]">
                <thead>
                    <tr>
                        <th class="w-10">#</th>
                        <th>{{ __('applicants.first_name') }}</th>
                        <th>{{ __('applicants.middle_name') }}</th>
                        <th>{{ __('applicants.last_name') }}</th>
                        <th>{{ __('applicants.nin') }}</th>
                        <th>{{ __('applicants.dob') }}</th>
                        <th>{{ __('applicants.sex') }}</th>
                        <th>{{ __('applicants.marital_status') }}</th>
                        <th>{{ __('common.phone') }}</th>
                        <th>{{ __('common.email') }}</th>
                        <th>{{ __('groups.leadership') }}</th>
                        @if($canManage)
                            <th class="text-right">{{ __('common.actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($group->members as $index => $member)
                    <tr>
                        <td class="text-slate-400 text-xs">{{ $index + 1 }}</td>
                        <td class="font-medium text-slate-900 dark:text-white whitespace-nowrap">
                            {{ $member->first_name }}
                            @if($member->is_group_leader)
                                <span class="text-xs font-normal text-indigo-600 dark:text-indigo-400">({{ __('groups.group_leader') }})</span>
                            @endif
                        </td>
                        <td class="text-slate-700 dark:text-zinc-300 whitespace-nowrap">{{ $member->middle_name ?: '—' }}</td>
                        <td class="font-medium text-slate-900 dark:text-white whitespace-nowrap">{{ $member->last_name }}</td>
                        <td class="font-mono text-xs whitespace-nowrap">{{ $member->nin }}</td>
                        <td>{{ $member->dob?->translatedFormat('d M Y') ?? __('common.na') }}</td>
                        <td>{{ $member->sex ?? __('common.na') }}</td>
                        <td>{{ $member->marital_status ? __('applicants.marital_statuses.'.$member->marital_status) : __('common.na') }}</td>
                        <td class="whitespace-nowrap">{{ $member->phone }}</td>
                        <td class="max-w-[160px] truncate" title="{{ $member->email }}">{{ $member->email ?? __('common.na') }}</td>
                        <td>{{ $member->leadershipRoleLabel() ?? __('common.na') }}</td>
                        @if($canManage)
                        <td class="text-right whitespace-nowrap">
                            <div class="inline-flex flex-wrap justify-end gap-1">
                                <button type="button" class="app-btn app-btn-secondary text-xs px-2.5 py-1.5"
                                    data-member-action="view" data-member-index="{{ $index }}">{{ __('common.view') }}</button>
                                <button type="button" class="app-btn app-btn-secondary text-xs px-2.5 py-1.5"
                                    data-member-action="edit" data-member-index="{{ $index }}">{{ __('common.edit') }}</button>
                                @if(! $member->is_group_leader)
                                <button type="button" class="app-btn app-btn-danger text-xs px-2.5 py-1.5"
                                    data-member-action="remove" data-member-index="{{ $index }}">{{ __('groups.remove_member') }}</button>
                                @endif
                            </div>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="md:hidden divide-y divide-slate-100 dark:divide-white/[0.06]">
            @foreach($group->members as $index => $member)
            <div class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900 dark:text-white truncate">{{ $member->first_name }} {{ $member->last_name }}</p>
                        @if($member->middle_name)
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('applicants.middle_name') }}: {{ $member->middle_name }}</p>
                        @endif
                        <p class="text-xs font-mono text-slate-500 mt-0.5">{{ $member->nin }}</p>
                    </div>
                    @if($member->is_group_leader)
                        @include('partials.badge', [
                            'variant' => 'primary',
                            'text' => __('groups.group_leader'),
                        ])
                    @endif
                </div>
                <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                    <div>
                        <dt class="text-[10px] uppercase tracking-wide text-slate-400">{{ __('applicants.dob') }}</dt>
                        <dd>{{ $member->dob?->translatedFormat('d M Y') ?? __('common.na') }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] uppercase tracking-wide text-slate-400">{{ __('applicants.sex') }}</dt>
                        <dd>{{ $member->sex ?? __('common.na') }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] uppercase tracking-wide text-slate-400">{{ __('applicants.marital_status') }}</dt>
                        <dd>{{ $member->marital_status ? __('applicants.marital_statuses.'.$member->marital_status) : __('common.na') }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] uppercase tracking-wide text-slate-400">{{ __('common.phone') }}</dt>
                        <dd class="break-all">{{ $member->phone }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-[10px] uppercase tracking-wide text-slate-400">{{ __('groups.leadership') }}</dt>
                        <dd>{{ $member->leadershipRoleLabel() ?? __('common.na') }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-[10px] uppercase tracking-wide text-slate-400">{{ __('common.email') }}</dt>
                        <dd class="break-all">{{ $member->email ?? __('common.na') }}</dd>
                    </div>
                </dl>
                @if($canManage)
                <div class="flex flex-wrap gap-2 pt-1">
                    <button type="button" class="app-btn app-btn-secondary text-xs"
                        data-member-action="view" data-member-index="{{ $index }}">{{ __('common.view') }}</button>
                    <button type="button" class="app-btn app-btn-secondary text-xs"
                        data-member-action="edit" data-member-index="{{ $index }}">{{ __('common.edit') }}</button>
                    @if(! $member->is_group_leader)
                    <button type="button" class="app-btn app-btn-danger text-xs"
                        data-member-action="remove" data-member-index="{{ $index }}">{{ __('groups.remove_member') }}</button>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    @if($canManage)
        @include('partials.modal', [
            'name' => 'view-member',
            'modalId' => 'app-modal-view-member',
            'title' => __('common.view'),
            'wide' => true,
            'body' => view('my_group._member_view_modal_body')->render(),
        ])

        @include('partials.modal', [
            'name' => 'edit-member',
            'modalId' => 'app-modal-edit-member',
            'title' => __('groups.edit_member'),
            'wide' => true,
            'body' => view('my_group._member_edit_modal_body')->render(),
        ])

        @include('partials.confirm-modal', [
            'name' => 'remove-member',
            'title' => __('groups.remove_member'),
            'message' => __('groups.remove_member_confirm'),
            'body' => '<p class="font-semibold text-slate-900 dark:text-white" data-remove-member-name></p>',
            'footer' => view('my_group._remove_member_confirm_footer')->render(),
        ])

        @include('partials.modal', [
            'name' => 'add',
            'title' => __('groups.add_member'),
            'wide' => true,
            'body' => view('my_group._member_form', ['action' => route('my-group.members.store')])->render(),
        ])
    @endif
</div>
@endsection
