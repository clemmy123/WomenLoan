<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('application_reports.title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        .meta { color: #64748b; margin-bottom: 16px; font-size: 10px; }
        .summary { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .summary td { padding: 6px 8px; border: 1px solid #e2e8f0; }
        .summary td:first-child { font-weight: bold; background: #f8fafc; width: 35%; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #4f46e5; color: #fff; padding: 8px 6px; text-align: left; font-size: 10px; }
        table.data td { padding: 7px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .amount { text-align: right; white-space: nowrap; }
        .members { font-size: 9px; color: #64748b; margin-top: 2px; }
        .footer { margin-top: 16px; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>
    @include('partials.report-pdf-letterhead', [
        'reportTitle' => __('application_reports.title'),
    ])

    <p class="meta">
        {{ __('reports.fiscal_year') }}: {{ ($filters['fiscal_year'] ?? null) === \App\Support\FiscalYear::ALL_KEY ? __('reports.all_years') : ($filters['fiscal_year'] ?? '—') }}
        &nbsp;|&nbsp;
        {{ __('application_reports.status') }}: {{ $filters['status'] ? loan_status_label($filters['status']) : __('application_reports.all_statuses') }}
        &nbsp;|&nbsp;
        {{ __('application_reports.date_from') }}: {{ $filters['date_from'] ?? '—' }}
        &nbsp;|&nbsp;
        {{ __('application_reports.date_to') }}: {{ $filters['date_to'] ?? '—' }}
        &nbsp;|&nbsp;
        {{ __('reports.generated_at') }}: {{ now()->translatedFormat('d M Y H:i') }}
    </p>

    <table class="summary">
        <tr>
            <td>{{ __('reports.total_records') }}</td>
            <td>{{ number_format($rows->count()) }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>{{ __('application_reports.track_id') }}</th>
                <th>{{ __('application_reports.full_name') }}</th>
                <th class="amount">{{ __('application_reports.amount_requested') }}</th>
                <th class="amount">{{ __('application_reports.amount_disbursed') }}</th>
                <th class="amount">{{ __('application_reports.outstanding') }}</th>
                <th class="amount">{{ __('application_reports.amount_repaid') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>{{ $row['track_id'] }}</td>
                <td>
                    {{ $row['full_name'] }}
                    @if(($row['loan_type'] ?? null) === 'group')
                        <div class="members">
                            {{ __('application_reports.group_members') }}:
                            {{ ! empty($row['members']) ? implode(', ', $row['members']) : '—' }}
                        </div>
                    @endif
                </td>
                <td class="amount">{{ format_tzs($row['amount_requested']) }}</td>
                <td class="amount">{{ format_tzs($row['amount_disbursed']) }}</td>
                <td class="amount">{{ format_tzs($row['outstanding']) }}</td>
                <td class="amount">{{ format_tzs($row['amount_repaid']) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6">{{ __('application_reports.no_results') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">{{ __('reports.pdf_fund') }} — {{ __('application_reports.title') }}</p>
</body>
</html>
