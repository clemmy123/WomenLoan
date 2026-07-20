<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #0f766e; }
        .summary { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .summary td { padding: 5px 7px; border: 1px solid #e2e8f0; }
        .summary td:first-child { font-weight: bold; background: #f8fafc; width: 40%; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        @include('partials.report-pdf-data-table-styles', ['pdfThPadding' => '6px 5px', 'pdfThFontSize' => '9px'])
        table.data td { padding: 5px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .amount { text-align: right; white-space: nowrap; }
        .footer { margin-top: 14px; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <x-report-pdf-meta-bar>
        {{ __('analytical_reports.fiscal_year') }}: {{ ($filters['fiscal_year'] ?? null) === \App\Support\FiscalYear::ALL_KEY ? __('analytical_reports.all_years') : ($filters['fiscal_year'] ?? '—') }}
        &nbsp;|&nbsp;
        {{ __('analytical_reports.date_from') }}: {{ $filters['date_from'] ?? '—' }}
        &nbsp;|&nbsp;
        {{ __('analytical_reports.date_to') }}: {{ $filters['date_to'] ?? '—' }}
        &nbsp;|&nbsp;
        {{ __('reports.generated_at') }}: {{ now()->translatedFormat('d M Y H:i') }}
    </x-report-pdf-meta-bar>

    <table class="summary">
        <tr>
            <td>{{ __('analytical_reports.debt_count') }}</td>
            <td>{{ number_format($summary['count']) }}</td>
        </tr>
        <tr>
            <td>{{ __('analytical_reports.col_disbursed') }}</td>
            <td>{{ format_tzs($summary['total_disbursed']) }}</td>
        </tr>
        <tr>
            <td>{{ __('analytical_reports.col_outstanding') }}</td>
            <td>{{ format_tzs($summary['total_outstanding']) }}</td>
        </tr>
        <tr>
            <td>{{ __('analytical_reports.average_elapsed') }}</td>
            <td>{{ number_format($summary['average_elapsed_days']) }} {{ __('analytical_reports.days_unit') }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>{{ __('analytical_reports.col_name') }}</th>
                <th class="amount">{{ __('analytical_reports.col_disbursed') }}</th>
                <th class="amount">{{ __('analytical_reports.col_outstanding') }}</th>
                <th>{{ __('analytical_reports.col_elapsed') }}</th>
                <th>{{ __('dashboard.track_id') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td class="amount">{{ format_tzs($row['disbursed']) }}</td>
                    <td class="amount">{{ format_tzs($row['outstanding']) }}</td>
                    <td>{{ $row['elapsed_label'] }}</td>
                    <td>{{ $row['track_id'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">{{ __('analytical_reports.no_debt_results') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">WDF Analytical Debt Report</p>
</body>
</html>
