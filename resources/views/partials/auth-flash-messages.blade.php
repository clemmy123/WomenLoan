@if(session('status'))
    <div class="app-flash-stack" data-auto-dismiss>
        @include('partials.status-card', [
            'type' => 'success',
            'message' => session('status'),
            'toast' => true,
        ])
    </div>
@endif

@if($errors->any())
    <div class="app-flash-stack" data-auto-dismiss>
        @include('partials.status-card', [
            'type' => 'error',
            'message' => $errors->count() === 1 ? $errors->first() : __('common.errors_below'),
            'errors' => $errors->count() > 1 ? $errors->all() : [],
            'toast' => true,
        ])
    </div>
@endif
