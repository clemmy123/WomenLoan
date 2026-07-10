<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('repayments.payments_title') }}</title>
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
    <h1>{{ __('repayments.payments_title') }}</h1>
    <p class="meta">
        {{ __('dashboard.status') }}: {{ $statusLabel }}
        &nbsp;|&nbsp;
        {{ __('common.search') }}: {{ $filters['search'] !== '' ? $filters['search'] : '—' }}
        &nbsp;|&nbsp;
        {{ __('reports.generated_at') }}: {{ now()->translatedFormat('d M Y, h:i:s A') }}
    </p>

    <table class="summary">
        <tr>
            <td>{{ __('repayments.summary_loans') }}</td>
            <td>{{ number_format($summary['count']) }}</td>
        </tr>
        <tr>
            <td>{{ __('repayments.disbursed_col') }}</td>
            <td>{{ format_tzs($summary['total_disbursed']) }}</td>
        </tr>
        <tr>
            <td>{{ __('repayments.amount_paid_col') }}</td>
            <td>{{ format_tzs($summary['total_paid']) }}</td>
        </tr>
        <tr>
            <td>{{ __('repayments.outstanding') }}</td>
            <td>{{ format_tzs($summary['total_outstanding']) }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>{{ __('dashboard.track_id') }}</th>
                <th>{{ __('loans.applicant_name') }}</th>
                <th class="amount">{{ __('repayments.disbursed_col') }}</th>
                <th class="amount">{{ __('repayments.amount_paid_col') }}</th>
                <th class="amount">{{ __('repayments.outstanding') }}</th>
                <th>{{ __('dashboard.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>{{ $row['track_id'] }}</td>
                <td>{{ $row['name'] }}</td>
                <td class="amount">{{ format_tzs($row['disbursed']) }}</td>
                <td class="amount">{{ format_tzs($row['paid']) }}</td>
                <td class="amount">{{ format_tzs($row['outstanding']) }}</td>
                <td>{{ $row['status'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6">{{ __('repayments.no_records') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">{{ __('nav.welcome') }}</p>
</body>
</html>
