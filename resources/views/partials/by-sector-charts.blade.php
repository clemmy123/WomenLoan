<div class="mb-6 rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
    <h2 class="font-bold text-slate-900 dark:text-white mb-1">{{ __('by_sector_reports.chart_all_sectors') }}</h2>
    <p class="text-xs text-slate-500 dark:text-zinc-400 mb-4">{{ __('by_sector_reports.chart_all_sectors_help') }}</p>
    <div class="h-80 lg:h-96"><canvas id="bySectorAllSectorsChart"></canvas></div>
</div>

@include('partials.by-report-charts')
