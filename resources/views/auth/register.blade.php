@extends('layouts.auth-form')

@section('auth_title', __('nav.register'))

@push('head')
<style>
    /* Register-only: hide Alpine dump before CSS/JS ready */
    [x-cloak]{display:none!important}
    .app-a11y-panel[x-cloak]{display:none!important}
    .nida-wizard:not([data-ready]) .nida-error,
    .nida-wizard:not([data-ready]) .nida-panel{display:none!important}
    .nida-wizard:not([data-ready]) .nida-panel[data-panel="nin"]{display:block!important}
    .nida-wizard:not([data-ready]) .nida-panel[data-panel="account"]{display:none!important}
    /* Register-only shell — form only, no left panel */
    .jj-auth-page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem 1.25rem;background:radial-gradient(ellipse 80% 60% at 20% 10%,rgba(26,86,176,.08),transparent 55%),radial-gradient(ellipse 70% 50% at 90% 90%,rgba(13,148,136,.07),transparent 50%),linear-gradient(180deg,#f4f8fb 0%,#fafcfd 45%,#fff 100%);box-sizing:border-box}
    .jj-auth-frame{width:min(520px,100%);margin:0 auto}
    .jj-auth-shell,.jj-auth-shell--form-only{display:block;border-radius:28px;overflow:hidden;background:#fff;border:1px solid rgba(15,23,42,.06);box-shadow:0 8px 30px rgba(10,37,64,.06),0 28px 60px rgba(10,37,64,.08)}
    .jj-auth-right{display:flex;flex-direction:column;padding:1.35rem 1.55rem 1.15rem;background:#fff}
    .jj-auth-card-toolbar{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:1rem}
    .jj-auth-toolbar-actions{display:inline-flex;align-items:center;gap:.45rem}
    .jj-auth-copy{margin:1.15rem 0 0;text-align:center;font-size:.72rem;color:#94a3b8}
    .jj-auth-page .auth-form-wrap,.jj-auth-page .auth-split-form-wrap{width:100%;max-width:none;margin:0;padding:0}
    .jj-auth-page .auth-split-form-header{margin-bottom:1.15rem}
    .jj-auth-page .auth-split-form-title{margin:0 0 .45rem;font-size:1.55rem;line-height:1.2;font-weight:800;letter-spacing:-.02em;color:#0b2f6b}
    .jj-auth-page .jj-auth-intro{margin:0;max-width:42ch;font-size:.92rem;line-height:1.55;color:#64748b;font-weight:500}
    .jj-auth-page .nida-panel[data-panel="nin"] .auth-split-field{padding:1rem 1.05rem;border-radius:16px;background:#f8fafc;border:1px solid rgba(15,23,42,.06)}
    .jj-auth-page .nida-panel[data-panel="nin"] .nida-hint{margin:.35rem 0 .65rem;color:#64748b}
    .jj-auth-page .nida-panel[data-panel="nin"] .auth-split-submit{margin-top:1rem}
    .jj-auth-page .nida-panel[data-panel="preview"] .nida-preview-footer{display:flex;align-items:center;justify-content:space-between;gap:.85rem;margin-top:.85rem}
    .jj-auth-page .nida-panel[data-panel="preview"] .nida-preview-hint{margin:0;flex:1;min-width:0;font-size:.8rem;line-height:1.45;color:#64748b;font-weight:500}
    .jj-auth-page .nida-panel[data-panel="preview"] .nida-preview-footer .auth-split-submit{margin:0;width:auto;flex:0 0 auto;padding:.55rem 1rem;font-size:.82rem;border-radius:12px;white-space:nowrap}
    @media (max-width:420px){
        .jj-auth-page .nida-panel[data-panel="preview"] .nida-preview-footer{flex-direction:column;align-items:stretch}
        .jj-auth-page .nida-panel[data-panel="preview"] .nida-preview-footer .auth-split-submit{width:100%;white-space:normal}
    }
    .jj-auth-page .nida-panel[data-panel="preview"] .nida-identity-card{gap:.7rem;margin:0;padding:.8rem;border-radius:1rem}
    .jj-auth-page .nida-panel[data-panel="preview"] .nida-identity-header{display:flex;flex-direction:column;align-items:center;gap:.65rem;text-align:center;padding-bottom:.7rem}
    .jj-auth-page .nida-panel[data-panel="preview"] .nida-verified-pill{display:block;padding:0;border-radius:0;background:transparent!important;color:#0b2f6b;font-size:.82rem;font-weight:700;letter-spacing:.01em}
    .jj-auth-page .nida-panel[data-panel="preview"] .nida-identity-photo-wrap{display:flex;justify-content:center}
    .jj-auth-page .nida-panel[data-panel="preview"] .nida-identity-photo{width:96px;height:120px;border-radius:.65rem;object-fit:cover}
    .jj-auth-page .nida-panel[data-panel="preview"] .nida-identity-grid{gap:.4rem .5rem}
    .jj-auth-page .nida-panel[data-panel="preview"] .nida-identity-field{padding:.35rem .45rem}
    .jj-auth-page .nida-panel[data-panel="preview"] .nida-identity-grid dt{font-size:.58rem}
    .jj-auth-page .nida-panel[data-panel="preview"] .nida-identity-grid dd{margin:.12rem 0 0;font-size:.78rem}
    .jj-auth-page.is-preview-step{padding:.75rem 1rem;align-items:flex-start}
    .jj-auth-page.is-preview-step .jj-auth-right{padding:1rem 1.15rem .85rem}
    .jj-auth-page.is-preview-step .jj-auth-card-toolbar{margin-bottom:.65rem}
    .jj-auth-page.is-preview-step .jj-auth-copy{display:none}
    .jj-auth-page .nida-demo-answers{margin:0.85rem 0 0;text-align:center;font-size:.8rem;color:#64748b}
</style>
@endpush

@section('content')
@php
    $nidaEnabled = (bool) config('services.nida.enabled');
@endphp

<div
    class="auth-split-form-wrap auth-form-wrap @if($nidaEnabled) nida-wizard @endif"
    @if ($nidaEnabled)
        x-data="nidaRegisterWizard({
            startUrl: @js(route('nida.api.start')),
            answerUrl: @js(route('nida.api.answer')),
            oldNin: @js(old('nin', '')),
            labels: {
                wrongAnswer: @js(__('nida.challenge_failed')),
            },
        })"
        x-init="$el.setAttribute('data-ready', '1'); $watch('step', (value) => document.body.classList.toggle('is-preview-step', value === 'preview'))"
        :data-step="step"
    @endif
>
    @include('partials.auth-flash-messages')

    @if ($nidaEnabled)
        <p class="nida-error" x-show="error" x-text="error" x-cloak role="alert"></p>

        {{-- Step 1: NIN only --}}
        <div class="auth-split-form nida-panel" data-panel="nin" x-show="step === 'nin'">
            <div class="auth-split-form-header">
                <h2 class="auth-split-form-title">{{ __('auth.register_title') }}</h2>
                <p class="jj-auth-intro">{{ __('nida.register_subtitle_nida') }}</p>
            </div>
            <div class="auth-split-field">
                <label class="auth-split-label" for="nida_nin">{{ __('applicants.nin') }} @include('partials.required-mark')</label>
                <p class="nida-hint">{{ __('nida.nin_hint') }}</p>
                <input
                    type="text"
                    id="nida_nin"
                    inputmode="numeric"
                    autocomplete="off"
                    maxlength="23"
                    data-nin-input
                    class="auth-split-input w-full app-nin-input"
                    placeholder="19000000-00000-00000-00"
                    x-model="nin"
                    @keydown.enter.prevent="startNin()"
                    autofocus
                >
            </div>
            <button type="button" class="auth-split-submit" @click="startNin()" :disabled="loading || nin.replace(/\D/g,'').length < 20">
                <span x-text="loading ? @js(__('common.loading')) : @js(__('nida.continue_nin'))"></span>
            </button>
        </div>

        {{-- Step 2: Security question only --}}
        <div class="auth-split-form nida-panel" data-panel="question" x-show="step === 'question'" x-cloak>
            <div class="auth-split-form-header">
                <h2 class="auth-split-form-title">{{ __('nida.step_questions') }}</h2>
                <p class="jj-auth-intro">{{ __('nida.question_step_intro') }}</p>
            </div>
            <div class="nida-question-card">
                <p class="nida-question-code" x-text="rqCode"></p>
                <p class="nida-question-text" x-text="question"></p>
            </div>
            <div class="auth-split-field">
                <label class="auth-split-label" for="nida_answer">{{ __('nida.answer_label') }} @include('partials.required-mark')</label>
                <input id="nida_answer" type="text" class="auth-split-input" x-model="answer" @keydown.enter.prevent="submitAnswer()" autocomplete="off">
            </div>
            <button type="button" class="auth-split-submit" @click="submitAnswer()" :disabled="loading || !answer.trim()">
                <span x-text="loading ? @js(__('common.loading')) : @js(__('nida.submit_answer'))"></span>
            </button>
            @if ((string) config('services.nida.driver', 'fake') === 'fake')
                <p class="nida-hint nida-demo-answers">{{ __('nida.demo_answers_hint') }}</p>
            @endif
        </div>

        {{-- Step 3: Profile review only --}}
        <div class="nida-panel" data-panel="preview" x-show="step === 'preview'" x-cloak>
            <div class="nida-identity-card" x-show="identity">
                <div class="nida-identity-header">
                    <span class="nida-verified-pill">{{ __('nida.verified_badge') }}</span>
                    <div class="nida-identity-photo-wrap">
                        <template x-if="photoSrc()">
                            <img :src="photoSrc()" alt="" class="nida-identity-photo" width="96" height="120">
                        </template>
                    </div>
                </div>
                <dl class="nida-identity-grid">
                    <div class="nida-identity-field">
                        <dt>{{ __('applicants.first_name') }}</dt>
                        <dd x-text="identity?.first_name"></dd>
                    </div>
                    <div class="nida-identity-field">
                        <dt>{{ __('applicants.middle_name') }}</dt>
                        <dd x-text="identity?.middle_name || '—'"></dd>
                    </div>
                    <div class="nida-identity-field">
                        <dt>{{ __('applicants.last_name') }}</dt>
                        <dd x-text="identity?.last_name"></dd>
                    </div>
                    <div class="nida-identity-field">
                        <dt>{{ __('applicants.sex') }}</dt>
                        <dd x-text="identity?.sex"></dd>
                    </div>
                    <div class="nida-identity-field">
                        <dt>{{ __('applicants.dob') }}</dt>
                        <dd x-text="identity?.dob"></dd>
                    </div>
                    <div class="nida-identity-field">
                        <dt>{{ __('applicants.age') }}</dt>
                        <dd x-text="identity?.age"></dd>
                    </div>
                    <div class="nida-identity-field nida-identity-field--full">
                        <dt>{{ __('applicants.nin') }}</dt>
                        <dd class="nida-mono" x-text="identity?.nin"></dd>
                    </div>
                    <div class="nida-identity-field nida-identity-field--full">
                        <dt>{{ __('applicants.nationality') }}</dt>
                        <dd x-text="identity?.nationality"></dd>
                    </div>
                </dl>
            </div>
            <div class="nida-preview-footer">
                <p class="nida-preview-hint">{{ __('nida.preview_continue_hint') }}</p>
                <button type="button" class="auth-split-submit" @click="continueToAccount()">
                    <span>{{ __('nida.continue_account') }}</span>
                    <svg class="auth-split-submit-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('register') }}"
        class="auth-split-form nida-panel"
        data-panel="account"
        @if ($nidaEnabled)
            x-show="step === 'account'"
            x-cloak
        @endif
    >
        @csrf

        @if ($nidaEnabled)
            <div class="auth-split-form-header">
                <h2 class="auth-split-form-title">{{ __('nida.step_account') }}</h2>
                <p class="jj-auth-intro">{{ __('nida.account_step_intro') }}</p>
            </div>

            <input type="hidden" name="nin" :value="identity?.nin || ''">
            <input type="hidden" name="first_name" :value="identity?.first_name || ''">
            <input type="hidden" name="middle_name" :value="identity?.middle_name || ''">
            <input type="hidden" name="last_name" :value="identity?.last_name || ''">
        @else
            <div class="auth-split-form-header">
                <h2 class="auth-split-form-title">{{ __('auth.register_title') }}</h2>
                <p class="jj-auth-intro">{{ __('auth.register_subtitle') }}</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="auth-split-field">
                    <label class="auth-split-label" for="first_name">{{ __('applicants.first_name') }} @include('partials.required-mark')</label>
                    <div class="auth-split-input-wrap">
                        <span class="auth-split-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.75"/><path d="M5 19c0-3.3 3.1-5 7-5s7 1.7 7 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                        </span>
                        <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required autofocus class="auth-split-input" placeholder="{{ __('applicants.first_name') }}">
                    </div>
                </div>
                <div class="auth-split-field">
                    <label class="auth-split-label" for="middle_name">{{ __('applicants.middle_name') }}</label>
                    <div class="auth-split-input-wrap">
                        <span class="auth-split-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.75"/><path d="M5 19c0-3.3 3.1-5 7-5s7 1.7 7 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                        </span>
                        <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name') }}" class="auth-split-input" placeholder="{{ __('applicants.middle_name') }}">
                    </div>
                </div>
                <div class="auth-split-field">
                    <label class="auth-split-label" for="last_name">{{ __('applicants.last_name') }} @include('partials.required-mark')</label>
                    <div class="auth-split-input-wrap">
                        <span class="auth-split-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.75"/><path d="M5 19c0-3.3 3.1-5 7-5s7 1.7 7 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                        </span>
                        <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required class="auth-split-input" placeholder="{{ __('applicants.last_name') }}">
                    </div>
                </div>
            </div>
        @endif

        <div class="auth-split-field">
            <label class="auth-split-label" for="email">{{ __('common.email') }} @include('partials.required-mark')</label>
            <div class="auth-split-input-wrap">
                <span class="auth-split-input-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4V6z" stroke="currentColor" stroke-width="1.75"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required class="auth-split-input" placeholder="you@example.com" @if($nidaEnabled) autofocus @endif>
            </div>
        </div>

        <div class="auth-split-field">
            <label class="auth-split-label" for="phone_local">{{ __('auth.phone') }} @include('partials.required-mark')</label>
            @include('partials.inputs.phone-input', [
                'name' => 'phone',
                'id' => 'phone_local',
                'value' => old('phone'),
                'required' => true,
                'class' => 'auth-form-phone-local',
            ])
        </div>

        <div class="auth-split-field" x-data="{ showPassword: false }">
            <label class="auth-split-label" for="password">{{ __('common.password') }} @include('partials.required-mark')</label>
            <div class="auth-split-input-wrap">
                <span class="auth-split-input-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.75"/><path d="M8 11V8a4 4 0 118 0v3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                </span>
                <input :type="showPassword ? 'text' : 'password'" name="password" id="password" required class="auth-split-input auth-split-input--password" placeholder="••••••••">
                <button type="button" @click="showPassword = !showPassword" class="auth-split-password-toggle"
                    :aria-label="showPassword ? @json(__('auth.hide_password')) : @json(__('auth.show_password'))">
                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @include('partials.password-requirements', ['targetId' => 'password', 'variant' => 'auth'])
        </div>

        <div class="auth-split-field" x-data="{ showPassword: false }">
            <label class="auth-split-label" for="password_confirmation">{{ __('common.confirm_password') }} @include('partials.required-mark')</label>
            <div class="auth-split-input-wrap">
                <span class="auth-split-input-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.75"/><path d="M8 11V8a4 4 0 118 0v3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                </span>
                <input :type="showPassword ? 'text' : 'password'" name="password_confirmation" id="password_confirmation" required class="auth-split-input auth-split-input--password" placeholder="••••••••">
                <button type="button" @click="showPassword = !showPassword" class="auth-split-password-toggle"
                    :aria-label="showPassword ? @json(__('auth.hide_password')) : @json(__('auth.show_password'))">
                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" class="auth-split-submit">
            <span>{{ __('nav.register') }}</span>
            <svg class="auth-split-submit-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M14 4h4v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 14 18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M18 6h-5M18 6v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6 8v10a2 2 0 002 2h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
    </form>

    <div class="auth-split-footer-link">
        <span>{{ __('auth.login_prompt') }}</span>
        <a href="{{ route('login') }}">{{ __('home.sign_in') }}</a>
    </div>
</div>
@endsection
