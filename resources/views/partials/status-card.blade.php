@php
    $type = $type ?? 'success';
    $message = $message ?? '';
    $errors = $errors ?? [];
    $autoDismiss = $autoDismiss ?? false;
    $toast = $toast ?? $autoDismiss;
    $title = $title ?? ($type === 'success' ? __('common.success') : __('common.error'));
    $class = trim(($class ?? '') . ' app-status-card app-status-card--' . $type . ($toast ? ' app-status-card--toast' : ''));
    $role = $type === 'success' ? 'status' : 'alert';
@endphp
<div class="{{ $class }}" role="{{ $role }}" @if($autoDismiss) data-auto-dismiss @endif>
    @unless($toast)
        <div class="app-status-card-accent" aria-hidden="true"></div>
    @endunless
    <div class="app-status-card-body">
        <div class="app-status-card-icon" aria-hidden="true">
            @if($type === 'success')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18"/>
                    <path d="m6 6 12 12"/>
                </svg>
            @endif
        </div>
        <div class="app-status-card-content">
            @if($toast && $title !== '')
                <h3 class="app-status-card-title">{{ $title }}</h3>
            @endif
            @if($message !== '')
                <p class="app-status-card-message">{{ $message }}</p>
            @endif
            @if(! empty($errors))
                <ul class="app-status-card-list">
                    @foreach($errors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
        @if($toast)
            <button type="button" class="app-status-card-ok" data-flash-dismiss>
                {{ __('common.ok') }}
            </button>
        @endif
    </div>
</div>
