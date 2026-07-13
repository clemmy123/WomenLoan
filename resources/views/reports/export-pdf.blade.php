<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('reports.title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #312e81; }
        .meta { color: #64748b; margin-bottom: 16px; font-size: 10px; }
        .summary { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .summary td { padding: 6px 8px; border: 1px solid #e2e8f0; }
        .summary td:first-child { font-weight: bold; background: #f8fafc; width: 35%; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #4f46e5; color: #fff; padding: 8px 6px; text-align: left; font-size: 10px; }
        table.data td { padding: 7px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .amount { text-align: right; white-space: nowrap; }
        .footer { margin-top: 16px; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>
    @include('partials.report-pdf-letterhead', [
        'reportTitle' => __('reports.title'),
    ])
    <p class="meta">
        {{ __('reports.fiscal_year') }}: {{ ($filters['fiscal_year'] ?? null) === \App\Support\FiscalYear::ALL_KEY ? __('reports.all_years') : ($filters['fiscal_year'] ?? '—') }}
        &nbsp;|&nbsp;
        {{ __('reports.date_from') }}: {{ $filters['date_from'] ?? '—' }}
        &nbsp;|&nbsp;
        {{ __('reports.date_to') }}: {{ $filters['date_to'] ?? '—' }}
        &nbsp;|&nbsp;
        {{ __('reports.generated_at') }}: {{ now()->translatedFormat('d M Y H:i') }}
    </p>

    <table class="summary">
        <tr>
            <td>{{ __('reports.total_records') }}</td>
            <td>{{ number_format($summary['count']) }}</td>
        </tr>
        <tr>
            <td>{{ __('reports.total_disbursed') }}</td>
            <td>{{ format_tzs($summary['total_disbursed']) }}</td>
        </tr>
        <tr>
            <td>{{ __('reports.total_paid') }}</td>
            <td>{{ format_tzs($summary['total_paid']) }}</td>
        </tr>
        <tr>
            <td>{{ __('reports.total_outstanding') }}</td>
            <td>{{ format_tzs($summary['total_outstanding']) }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>{{ __('reports.name') }}</th>
                <th>{{ __('dashboard.track_id') }}</th>
                <th>{{ __('common.region') }}</th>
                <th>{{ __('common.type') }}</th>
                <th class="amount">{{ __('reports.disbursed') }}</th>
                <th class="amount">{{ __('reports.paid') }}</th>
                <th class="amount">{{ __('reports.outstanding') }}</th>
                <th>{{ __('dashboard.date') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td>{{ $row['track_id'] }}</td>
                <td>{{ $row['region'] ?? '—' }}</td>
                <td>{{ $row['loan_type'] }}</td>
                <td class="amount">{{ format_tzs($row['disbursed']) }}</td>
                <td class="amount">{{ format_tzs($row['paid']) }}</td>
                <td class="amount">{{ format_tzs($row['outstanding']) }}</td>
                <td>{{ $row['date'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8">{{ __('reports.no_results') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">{{ __('reports.pdf_fund') }} — {{ __('reports.title') }}</p>
</body>
</html>
