@extends('layouts.app')

@section('title', __('admin.edit_user'))

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">{{ __('admin.edit_user') }}: {{ $user->name }}</h1>
</div>

<form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
    @csrf @method('PUT')
    @include('admin.users._form', ['user' => $user, 'userRoles' => $userRoles])
    <button type="submit" class="app-btn app-btn-primary">{{ __('admin.update_user') }}</button>
</form>
@endsection
