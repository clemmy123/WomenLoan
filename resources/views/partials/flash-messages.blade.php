@if(session('success') || session('error') || $errors->any())
<div class="app-flash-stack" data-auto-dismiss>
    @if(session('success'))
        @include('partials.status-card', [
            'type' => 'success',
            'message' => session('success'),
        ])
    @endif

    @if(session('error'))
        @include('partials.status-card', [
            'type' => 'error',
            'message' => session('error'),
        ])
    @endif

    @if($errors->any())
        @include('partials.status-card', [
            'type' => 'error',
            'message' => $errors->count() === 1 ? $errors->first() : __('common.errors_below'),
            'errors' => $errors->count() > 1 ? $errors->all() : [],
        ])
    @endif
</div>
@endif
