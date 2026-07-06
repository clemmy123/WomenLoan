@php
    $type = $type ?? 'success';
    $message = $message ?? '';
    $errors = $errors ?? [];
    $class = trim(($class ?? '') . ' app-status-card app-status-card--' . $type);
    $role = $type === 'success' ? 'status' : 'alert';
@endphp
<div class="{{ $class }}" role="{{ $role }}">
    <div class="app-status-card-accent" aria-hidden="true"></div>
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
</div>
