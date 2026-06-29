@extends('layouts.app')

@section('title', __('admin.edit_user'))

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">{{ __('admin.edit_user') }}: {{ $user->name }}</h1>
</div>

<form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
    @csrf @method('PUT')
    @include('admin.users._form', ['user' => $user, 'userRoles' => $userRoles])
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm">{{ __('admin.update_user') }}</button>
</form>
@endsection
