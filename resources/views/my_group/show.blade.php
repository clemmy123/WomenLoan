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
<div class="page page-medium" x-data="{ modal: null, member: null }">
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $group->name }}</h1>
            <p class="page-subtitle">{{ __('groups.my_group_subtitle') }}</p>
        </div>
        <div class="page-actions flex flex-wrap gap-2">
            @if($canManage)
                <button type="button" @click="modal = 'add'" class="app-btn app-btn-secondary">{{ __('groups.add_member') }}</button>
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
            @include('partials.detail-field', ['label' => __('groups.registered_on'), 'value' => $group->setup_completed_at?->translatedFormat('d M Y, H:i')])
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
                                    @click="member = @js($membersPayload[$index]); modal = 'view'">{{ __('common.view') }}</button>
                                <button type="button" class="app-btn app-btn-secondary text-xs px-2.5 py-1.5"
                                    @click="member = @js($membersPayload[$index]); modal = 'edit'">{{ __('common.edit') }}</button>
                                @if(! $member->is_group_leader)
                                <button type="button" class="app-btn app-btn-danger text-xs px-2.5 py-1.5"
                                    @click="member = @js($membersPayload[$index]); modal = 'remove'">{{ __('groups.remove_member') }}</button>
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
                        @click="member = @js($membersPayload[$index]); modal = 'view'">{{ __('common.view') }}</button>
                    <button type="button" class="app-btn app-btn-secondary text-xs"
                        @click="member = @js($membersPayload[$index]); modal = 'edit'">{{ __('common.edit') }}</button>
                    @if(! $member->is_group_leader)
                    <button type="button" class="app-btn app-btn-danger text-xs"
                        @click="member = @js($membersPayload[$index]); modal = 'remove'">{{ __('groups.remove_member') }}</button>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    @if($canManage)
        {{-- View member --}}
        <div x-show="modal === 'view' && member" x-cloak class="app-modal-root" role="dialog" aria-modal="true" @keydown.escape.window="modal = null">
            <div class="app-modal-backdrop" @click="modal = null"></div>
            <div class="app-modal-panel" @click.stop>
                <div class="app-modal-header">
                    <h3 class="app-modal-title" x-text="member?.full_name"></h3>
                    <button type="button" class="app-modal-close" @click="modal = null">&times;</button>
                </div>
                <div class="app-modal-body space-y-4">
                    <dl class="detail-grid">
                        <div><dt class="text-xs text-slate-500 uppercase">{{ __('applicants.first_name') }}</dt><dd class="font-medium" x-text="member?.first_name"></dd></div>
                        <div><dt class="text-xs text-slate-500 uppercase">{{ __('applicants.middle_name') }}</dt><dd x-text="member?.middle_name || '—'"></dd></div>
                        <div><dt class="text-xs text-slate-500 uppercase">{{ __('applicants.last_name') }}</dt><dd class="font-medium" x-text="member?.last_name"></dd></div>
                        <div><dt class="text-xs text-slate-500 uppercase">{{ __('groups.leadership') }}</dt><dd x-text="member?.leadership_role_label ?? '{{ __('common.na') }}'"></dd></div>
                        <div><dt class="text-xs text-slate-500 uppercase">{{ __('applicants.nin') }}</dt><dd class="font-mono text-sm" x-text="member?.nin"></dd></div>
                        <div><dt class="text-xs text-slate-500 uppercase">{{ __('applicants.dob') }}</dt><dd x-text="member?.dob_label ?? '—'"></dd></div>
                        <div><dt class="text-xs text-slate-500 uppercase">{{ __('applicants.sex') }}</dt><dd x-text="member?.sex ?? '—'"></dd></div>
                        <div><dt class="text-xs text-slate-500 uppercase">{{ __('applicants.marital_status') }}</dt><dd x-text="member?.marital_status_label ?? '—'"></dd></div>
                        <div><dt class="text-xs text-slate-500 uppercase">{{ __('common.phone') }}</dt><dd x-text="member?.phone"></dd></div>
                        <div class="md:col-span-2"><dt class="text-xs text-slate-500 uppercase">{{ __('common.email') }}</dt><dd x-text="member?.email || '—'"></dd></div>
                    </dl>
                    <div class="flex justify-end">
                        <button type="button" class="app-btn app-btn-secondary" @click="modal = null">{{ __('common.cancel') }}</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Edit member --}}
        <div x-show="modal === 'edit' && member" x-cloak class="app-modal-root" role="dialog" aria-modal="true" @keydown.escape.window="modal = null">
            <div class="app-modal-backdrop" @click="modal = null"></div>
            <div class="app-modal-panel max-w-lg" @click.stop>
                <div class="app-modal-header">
                    <h3 class="app-modal-title">{{ __('groups.edit_member') }}</h3>
                    <button type="button" class="app-modal-close" @click="modal = null">&times;</button>
                </div>
                <div class="app-modal-body">
                    <template x-if="member?.is_group_leader">
                        <form :action="member?.update_url" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <p class="text-sm text-slate-500 dark:text-zinc-400">{{ __('groups.leader_edit_hint') }}</p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="wizard-field">
                                    <label class="app-label">{{ __('applicants.first_name') }}</label>
                                    <input type="text" class="app-input" :value="member?.first_name" readonly>
                                </div>
                                <div class="wizard-field">
                                    <label class="app-label">{{ __('applicants.middle_name') }}</label>
                                    <input type="text" class="app-input" :value="member?.middle_name || ''" readonly>
                                </div>
                                <div class="wizard-field">
                                    <label class="app-label">{{ __('applicants.last_name') }}</label>
                                    <input type="text" class="app-input" :value="member?.last_name" readonly>
                                </div>
                            </div>
                            <div class="wizard-field">
                                <label class="app-label">{{ __('applicants.dob') }} @include('partials.required-mark')</label>
                                <input type="date" name="dob" class="app-input" :value="member?.dob" required>
                            </div>
                            <div class="wizard-field">
                                <label class="app-label">{{ __('applicants.sex') }} @include('partials.required-mark')</label>
                                @include('partials.inputs.female-sex-field')
                            </div>
                            <div class="wizard-field">
                                <label class="app-label">{{ __('groups.leadership') }}</label>
                                <select name="leadership_role" class="app-select">
                                    <option value="">{{ __('groups.select_leadership') }}</option>
                                    @foreach(\App\Support\GroupLeadershipRole::options() as $key => $label)
                                        <option value="{{ $key }}" :selected="member?.leadership_role === '{{ $key }}'">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" class="app-btn app-btn-secondary" @click="modal = null">{{ __('common.cancel') }}</button>
                                <button type="submit" class="app-btn app-btn-primary">{{ __('common.save') }}</button>
                            </div>
                        </form>
                    </template>
                    <template x-if="member && !member.is_group_leader">
                        <form :action="member.update_url" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="wizard-field">
                                    <label class="app-label">{{ __('applicants.first_name') }} @include('partials.required-mark')</label>
                                    <input type="text" name="first_name" class="app-input" :value="member.first_name" required>
                                </div>
                                <div class="wizard-field">
                                    <label class="app-label">{{ __('applicants.middle_name') }}</label>
                                    <input type="text" name="middle_name" class="app-input" :value="member.middle_name || ''">
                                </div>
                                <div class="wizard-field">
                                    <label class="app-label">{{ __('applicants.last_name') }} @include('partials.required-mark')</label>
                                    <input type="text" name="last_name" class="app-input" :value="member.last_name" required>
                                </div>
                            </div>
                            <div class="wizard-form-grid wizard-form-grid-2">
                                <div class="wizard-field">
                                    <label class="app-label">{{ __('applicants.nin') }} @include('partials.required-mark')</label>
                                    <input type="text" name="nin" class="app-input" :value="member.nin" required>
                                </div>
                                <div class="wizard-field">
                                    <label class="app-label">{{ __('applicants.dob') }} @include('partials.required-mark')</label>
                                    <input type="date" name="dob" class="app-input" :value="member.dob" required>
                                </div>
                                <div class="wizard-field">
                                    <label class="app-label">{{ __('applicants.sex') }} @include('partials.required-mark')</label>
                                    <input type="hidden" name="sex" value="Female">
                                    <input type="text" value="{{ __('applicants.female') }}" readonly
                                        class="app-input bg-gray-100 border-gray-200 text-gray-600 cursor-not-allowed">
                                </div>
                                <div class="wizard-field">
                                    <label class="app-label">{{ __('applicants.marital_status') }} @include('partials.required-mark')</label>
                                    <select name="marital_status" class="app-select" required>
                                        @foreach(\App\Models\Applicant::MARITAL_STATUSES as $status)
                                            <option value="{{ $status }}" :selected="member?.marital_status === '{{ $status }}'">{{ __('applicants.marital_statuses.'.$status) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="wizard-field">
                                    <label class="app-label">{{ __('common.phone') }} @include('partials.required-mark')</label>
                                    <input type="text" name="phone" class="app-input" :value="member.phone" required>
                                </div>
                                <div class="wizard-field">
                                    <label class="app-label">{{ __('common.email') }}</label>
                                    <input type="email" name="email" class="app-input" :value="member.email || ''">
                                </div>
                                <div class="wizard-field">
                                    <label class="app-label">{{ __('groups.leadership') }}</label>
                                    <select name="leadership_role" class="app-select">
                                        <option value="">{{ __('groups.select_leadership') }}</option>
                                        @foreach(\App\Support\GroupLeadershipRole::options() as $key => $label)
                                            <option value="{{ $key }}" :selected="member?.leadership_role === '{{ $key }}'">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" class="app-btn app-btn-secondary" @click="modal = null">{{ __('common.cancel') }}</button>
                                <button type="submit" class="app-btn app-btn-primary">{{ __('common.save') }}</button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </div>

        @include('partials.confirm-modal', [
            'show' => "modal === 'remove' && member",
            'close' => 'modal = null',
            'title' => __('groups.remove_member'),
            'message' => __('groups.remove_member_confirm'),
            'confirmLabel' => __('groups.remove_member'),
            'confirmVariant' => 'danger',
            'body' => '<p class="font-semibold text-slate-900 dark:text-white" x-text="member?.full_name"></p>',
            'footer' => view('my_group._remove_member_confirm_footer')->render(),
        ])

        {{-- Add member --}}
        @include('partials.modal', [
            'name' => 'add',
            'title' => __('groups.add_member'),
            'body' => view('my_group._member_form', ['action' => route('my-group.members.store')])->render(),
        ])
    @endif
</div>
@endsection
