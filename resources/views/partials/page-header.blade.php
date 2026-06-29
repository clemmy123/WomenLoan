<div class="page-header">
    <div>
        <h1 class="page-title">{{ $title }}</h1>
        @isset($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endisset
    </div>
    @isset($actions)
        <div class="page-actions">{!! $actions !!}</div>
    @endisset
</div>
