@if(session('success') || $errors->any())
<div class="app-flash-stack">
    @if(session('success'))
        <div class="app-alert app-alert-success" role="alert">
            <svg class="app-alert-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="app-alert-body">
                <p class="app-alert-title">{{ __('common.success') }}</p>
                <p class="app-alert-message">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="app-alert app-alert-error" role="alert">
            <svg class="app-alert-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="app-alert-body">
                <p class="app-alert-title">{{ __('common.errors_below') }}</p>
                <ul class="app-alert-list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>
@endif
