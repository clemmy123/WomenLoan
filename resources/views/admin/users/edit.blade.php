@extends('layouts.app')

@section('title', __('admin.edit_user'))

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">{{ __('admin.edit_user') }}: {{ $user->name }}</h1>
</div>

<form
    method="POST"
    action="{{ route('admin.users.update', $user) }}"
    class="space-y-6"
    data-staff-user-form
    data-role-zone-map='@json(\App\Support\StaffZone::GEO_ROLE_PRIORITY)'
    data-roles-required="{{ __('admin.roles_required') }}"
    data-geo-zone-required="{{ __('admin.geo_zone_required') }}"
    data-geo-zone-incomplete="{{ __('admin.geo_zone_incomplete') }}"
    data-complete-section-here="{{ __('admin.complete_section_here') }}"
    data-ok-label="{{ __('common.ok') }}"
    data-error-label="{{ __('common.error') }}"
    novalidate
>
    @csrf @method('PUT')
    @include('admin.users._form', ['user' => $user, 'userRoles' => $userRoles])
    <button type="submit" class="app-btn app-btn-primary">{{ __('admin.update_user') }}</button>
</form>
@endsection
