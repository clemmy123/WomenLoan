@props([
    'title',
    'help',
    'canvasId',
    'height' => 'h-80 lg:h-96',
])

<div class="mb-6 rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
    <h2 class="font-bold text-slate-900 dark:text-white mb-1">{{ $title }}</h2>
    <p class="text-xs text-slate-500 dark:text-zinc-400 mb-4">{{ $help }}</p>
    <div class="{{ $height }}"><canvas id="{{ $canvasId }}"></canvas></div>
</div>

@include('partials.by-report-charts')
