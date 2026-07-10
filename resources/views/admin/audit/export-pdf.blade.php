<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('nav.audit_logs') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #312e81; }
        .meta { color: #64748b; margin-bottom: 14px; font-size: 9px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #4f46e5; color: #fff; padding: 6px 5px; text-align: left; font-size: 9px; }
        table.data td { padding: 5px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .footer { margin-top: 14px; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
    <h1>{{ __('nav.audit_logs') }}</h1>
    <p class="meta">
        {{ __('audit.date_from') }}: {{ $filters['date_from'] ?: '—' }}
        &nbsp;|&nbsp;
        {{ __('audit.date_to') }}: {{ $filters['date_to'] ?: '—' }}
        &nbsp;|&nbsp;
        {{ __('audit.event') }}: {{ $filters['event'] ? __('audit.events.'.$filters['event']) : __('audit.all_events') }}
        &nbsp;|&nbsp;
        {{ __('audit.total_records') }}: {{ $rows->count() }}
        &nbsp;|&nbsp;
        {{ __('reports.generated_at') }}: {{ now()->translatedFormat('d M Y H:i') }}
    </p>

    <table class="data">
        <thead>
            <tr>
                <th>{{ __('audit.when') }}</th>
                <th>{{ __('audit.who') }}</th>
                <th>{{ __('audit.event') }}</th>
                <th>{{ __('audit.what') }}</th>
                <th>{{ __('audit.description') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['when'] }}</td>
                    <td>{{ $row['who'] }}</td>
                    <td>{{ $row['event'] }}</td>
                    <td>{{ $row['what'] }}</td>
                    <td>{{ $row['description'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">{{ __('audit.no_records') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">{{ __('nav.welcome') }}</p>
</body>
</html>
