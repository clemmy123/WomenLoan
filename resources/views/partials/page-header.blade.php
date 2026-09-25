<div class="app-page-header">
    <div>
        <h1 class="app-page-title">{{ $title }}</h1>
        @isset($subtitle)
            <p class="app-page-subtitle">{{ $subtitle }}</p>
        @endisset
    </div>
    @isset($actions)
        <div class="app-page-actions">{!! $actions !!}</div>
    @endisset
</div>
