@extends('layouts.guest')

@section('content')
<div class="bg-white dark:dark-surface rounded-3xl  border border-slate-100 dark:border-white/[0.08] p-8">
    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-6">{{ __('auth.register_title') }}</h2>

    @if($errors->any())
        <div class="mb-4 w-fit max-w-full rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 px-4 py-2.5 text-sm text-red-700 dark:text-red-300">
            <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-zinc-400 mb-1">{{ __('auth.full_name') }}</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-dm-800 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-zinc-400 mb-1">{{ __('common.email') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-dm-800 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-zinc-400 mb-1">{{ __('auth.phone') }}</label>
            @include('partials.inputs.phone-input', [
                'name' => 'phone',
                'value' => old('phone'),
                'required' => true,
                'class' => '',
            ])
            @error('phone') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-zinc-400 mb-1">{{ __('common.password') }}</label>
            <input type="password" name="password" required class="w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-dm-800 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-zinc-400 mb-1">{{ __('common.confirm_password') }}</label>
            <input type="password" name="password_confirmation" required class="w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-dm-800 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
        </div>
        <button type="submit" class="app-btn app-btn-primary app-btn-block">
            {{ __('nav.register') }}
        </button>
    </form>

    <p class="mt-6 text-center text-xs text-slate-500">
        {{ __('auth.login_prompt') }}
        <a href="{{ route('login') }}" class="text-indigo-600 font-semibold hover:underline">{{ __('nav.login') }}</a>
    </p>
</div>
@endsection
