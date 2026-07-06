@if (session('status'))
    @include('partials.status-card', [
        'type' => 'success',
        'message' => session('status'),
        'class' => 'app-status-card--auth',
    ])
@endif

@if($errors->any())
    @include('partials.status-card', [
        'type' => 'error',
        'message' => $errors->count() === 1 ? $errors->first() : __('common.errors_below'),
        'errors' => $errors->count() > 1 ? $errors->all() : [],
        'class' => 'app-status-card--auth',
    ])
@endif
