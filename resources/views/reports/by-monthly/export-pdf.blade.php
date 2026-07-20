<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('by_monthly_reports.title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #0f766e; }
        .summary { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .summary td { padding: 6px 8px; border: 1px solid #e2e8f0; }
        .summary td:first-child { font-weight: bold; background: #f8fafc; width: 40%; }
        table.data { width: 100%; border-collapse: collapse; }
        @include('partials.report-pdf-data-table-styles')
        table.data td { padding: 7px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .amount { text-align: right; white-space: nowrap; }
        .footer { margin-top: 16px; font-size: 9px; color: #94a3b8; }
        .sub { font-size: 9px; color: #64748b; }
    </style>
</head>
<body>
    @include('partials.report-pdf-letterhead', [
        'reportTitle' => __('by_monthly_reports.title'),
    ])
    <x-report-pdf-meta-bar>
        {{ __('by_monthly_reports.month') }}: {{ $monthLabel ?: __('by_monthly_reports.all_months') }}
        &nbsp;|&nbsp;
        {{ __('by_monthly_reports.date_from') }}: {{ $filters['date_from'] ?? '—' }}
        &nbsp;|&nbsp;
        {{ __('by_monthly_reports.date_to') }}: {{ $filters['date_to'] ?? '—' }}
        &nbsp;|&nbsp;
        {{ __('by_monthly_reports.generated_at') }}: {{ now()->translatedFormat('d M Y H:i') }}
    </x-report-pdf-meta-bar>

    <table class="summary">
        <tr>
            <td>{{ __('by_monthly_reports.total_disbursed') }}</td>
            <td>{{ format_tzs($summary['total_disbursed']) }}</td>
        </tr>
        <tr>
            <td>{{ __('by_monthly_reports.people_financed') }}</td>
            <td>{{ number_format(($summary['individual_count'] ?? 0) + ($summary['group_members_count'] ?? 0)) }}</td>
        </tr>
        <tr>
            <td>{{ __('by_monthly_reports.group_count') }}</td>
            <td>{{ number_format($summary['group_count']) }}</td>
        </tr>
        <tr>
            <td>{{ __('by_monthly_reports.total_outstanding') }}</td>
            <td>{{ format_tzs($summary['total_outstanding']) }}</td>
        </tr>
        <tr>
            <td>{{ __('by_monthly_reports.total_paid') }}</td>
            <td>{{ format_tzs($summary['total_paid']) }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>{{ __('by_monthly_reports.col_name') }}</th>
                <th class="amount">{{ __('by_monthly_reports.col_disbursed') }}</th>
                <th class="amount">{{ __('by_monthly_reports.col_outstanding') }}</th>
                <th class="amount">{{ __('by_monthly_reports.col_paid') }}</th>
                <th>{{ __('by_monthly_reports.col_phone') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>
                    {{ $row['name'] }}
                    <div class="sub">{{ $row['loan_type_label'] }} · {{ $row['month_label'] }} · {{ $row['track_id'] }}</div>
                </td>
                <td class="amount">{{ format_tzs($row['disbursed']) }}</td>
                <td class="amount">{{ format_tzs($row['outstanding']) }}</td>
                <td class="amount">{{ format_tzs($row['paid']) }}</td>
                <td>{{ $row['phone'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5">{{ __('by_monthly_reports.no_results') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">{{ __('reports.pdf_fund') }} — {{ __('by_monthly_reports.title') }}</p>
</body>
</html>
