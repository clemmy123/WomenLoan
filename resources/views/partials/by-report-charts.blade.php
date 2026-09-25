<div class="grid gap-6 lg:grid-cols-2 mb-6">
    <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
        <h2 class="font-bold text-slate-900 dark:text-white mb-1">{{ __('reports.by_list_chart_financial') }}</h2>
        <p class="text-xs text-slate-500 dark:text-zinc-400 mb-4">{{ __('reports.by_list_chart_financial_help') }}</p>
        <div class="h-64"><canvas id="byReportFinancialChart"></canvas></div>
    </div>
    <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6">
        <h2 class="font-bold text-slate-900 dark:text-white mb-1">{{ __('reports.loan_type_chart') }}</h2>
        <p class="text-xs text-slate-500 dark:text-zinc-400 mb-4">{{ __('reports.by_list_chart_type_help') }}</p>
        <div class="h-64 flex items-center justify-center"><canvas id="byReportTypeChart"></canvas></div>
    </div>
    <div class="rounded-2xl bg-white dark:dark-surface border border-slate-200 dark:border-white/[0.08] p-6 lg:col-span-2">
        <h2 class="font-bold text-slate-900 dark:text-white mb-1">{{ __('reports.by_list_chart_top_disbursed') }}</h2>
        <p class="text-xs text-slate-500 dark:text-zinc-400 mb-4">{{ __('reports.by_list_chart_top_disbursed_help') }}</p>
        <div class="h-72"><canvas id="byReportTopDisbursedChart"></canvas></div>
    </div>
</div>

<script type="application/json" id="by-list-reports-chart-data">@json($charts)</script>
@include('partials.chart-js', ['module' => 'js/pages/by-list-reports.js'])
