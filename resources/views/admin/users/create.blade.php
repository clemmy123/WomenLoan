@extends('layouts.app')

@section('title', __('admin.create_user'))

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">{{ __('admin.create_user') }}</h1>
    <p class="text-sm text-slate-500 mt-1">{{ __('admin.create_user_subtitle') }}</p>
</div>

<form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
    @csrf
    @include('admin.users._form')
    <button type="submit" class="app-btn app-btn-primary">{{ __('admin.create_user') }}</button>
</form>
@endsection
