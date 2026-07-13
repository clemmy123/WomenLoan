<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('analytical_reports.overview_title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #312e81; }
        h2 { font-size: 12px; margin: 18px 0 8px; color: #4338ca; }
        .meta { color: #64748b; margin-bottom: 14px; font-size: 9px; }
        .summary { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .summary td { padding: 5px 7px; border: 1px solid #e2e8f0; }
        .summary td:first-child { font-weight: bold; background: #f8fafc; width: 40%; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data th { background: #4f46e5; color: #fff; padding: 6px 5px; text-align: left; font-size: 9px; }
        table.data td { padding: 5px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .amount { text-align: right; white-space: nowrap; }
        .footer { margin-top: 14px; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
    @include('partials.report-pdf-letterhead', [
        'reportTitle' => __('analytical_reports.overview_title'),
    ])
    <p class="meta">
        {{ __('analytical_reports.fiscal_year') }}: {{ ($filters['fiscal_year'] ?? null) === \App\Support\FiscalYear::ALL_KEY ? __('analytical_reports.all_years') : ($filters['fiscal_year'] ?? '—') }}
        &nbsp;|&nbsp;
        {{ __('analytical_reports.date_from') }}: {{ $filters['date_from'] ?? '—' }}
        &nbsp;|&nbsp;
        {{ __('analytical_reports.date_to') }}: {{ $filters['date_to'] ?? '—' }}
        &nbsp;|&nbsp;
        @if(!empty($filters['quarter']))
            {{ __('analytical_reports.quarter') }}: {{ __('analytical_reports.period_'.$filters['quarter']) }}
        @else
            {{ __('analytical_reports.period') }}: {{ __('analytical_reports.period_'.$filters['period']) }}
        @endif
        &nbsp;|&nbsp;
        {{ __('reports.generated_at') }}: {{ now()->translatedFormat('d M Y H:i') }}
    </p>

    <table class="summary">
        <tr>
            <td>{{ __('analytical_reports.individual_count') }}</td>
            <td>{{ number_format($summary['individual_count']) }} ({{ format_tzs($summary['individual_disbursed']) }})</td>
        </tr>
        <tr>
            <td>{{ __('analytical_reports.group_count') }}</td>
            <td>{{ number_format($summary['group_count']) }} ({{ format_tzs($summary['group_disbursed']) }})</td>
        </tr>
        <tr>
            <td>{{ __('analytical_reports.total_paid') }}</td>
            <td>{{ format_tzs($summary['total_paid']) }}</td>
        </tr>
        <tr>
            <td>{{ __('analytical_reports.total_outstanding') }}</td>
            <td>{{ format_tzs($summary['total_outstanding']) }}</td>
        </tr>
    </table>

    <h2>{{ __('analytical_reports.individual_repayments') }}</h2>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('analytical_reports.col_name') }}</th>
                <th>{{ __('analytical_reports.col_bank') }}</th>
                <th>{{ __('analytical_reports.col_phone') }}</th>
                <th class="amount">{{ __('analytical_reports.col_disbursed') }}</th>
                <th class="amount">{{ __('analytical_reports.col_paid') }}</th>
                <th>{{ __('analytical_reports.col_paid_on') }}</th>
                <th class="amount">{{ __('analytical_reports.col_outstanding') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($individuals as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['bank'] }}</td>
                    <td>{{ $row['phone'] }}</td>
                    <td class="amount">{{ format_tzs($row['disbursed']) }}</td>
                    <td class="amount">{{ format_tzs($row['paid']) }}</td>
                    <td>{{ $row['paid_on'] }}</td>
                    <td class="amount">{{ format_tzs($row['outstanding']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">{{ __('analytical_reports.no_results') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>{{ __('analytical_reports.group_repayments') }}</h2>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('analytical_reports.col_group_name') }}</th>
                <th>{{ __('analytical_reports.col_members') }}</th>
                <th>{{ __('analytical_reports.col_location') }}</th>
                <th class="amount">{{ __('analytical_reports.col_disbursed') }}</th>
                <th class="amount">{{ __('analytical_reports.col_paid') }}</th>
                <th class="amount">{{ __('analytical_reports.col_outstanding') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groups as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['members_count'] }}</td>
                    <td>{{ $row['location'] }}</td>
                    <td class="amount">{{ format_tzs($row['disbursed']) }}</td>
                    <td class="amount">{{ format_tzs($row['paid']) }}</td>
                    <td class="amount">{{ format_tzs($row['outstanding']) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">{{ __('analytical_reports.no_results') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">{{ __('nav.welcome') }}</p>
</body>
</html>
