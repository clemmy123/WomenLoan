@props(['label', 'path' => null])

<div>
    <span class="detail-field-label">{{ $label }}</span>
    @if(filled($path))
        <a href="{{ asset('storage/'.$path) }}" target="_blank" rel="noopener noreferrer"
           class="detail-field-value inline-flex items-center gap-1.5 text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold break-all">
            <span>{{ basename($path) }}</span>
            <span class="text-xs font-medium">↗</span>
        </a>
    @else
        <span class="detail-field-value">{{ __('common.na') }}</span>
    @endif
</div>
