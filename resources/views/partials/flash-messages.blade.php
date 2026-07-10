@if(session('success') || session('error') || $errors->any())
<div class="app-flash-stack" data-auto-dismiss>
    @if(session('success'))
        @include('partials.status-card', [
            'type' => 'success',
            'message' => session('success'),
            'toast' => true,
        ])
    @endif

    @if(session('error'))
        @include('partials.status-card', [
            'type' => 'error',
            'message' => session('error'),
            'toast' => true,
        ])
    @endif

    @if($errors->any())
        @include('partials.status-card', [
            'type' => 'error',
            'message' => $errors->count() === 1 ? $errors->first() : __('common.errors_below'),
            'errors' => $errors->count() > 1 ? $errors->all() : [],
            'toast' => true,
        ])
    @endif
</div>
@endif
