<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('nav.users') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #0f766e; }
        .meta { color: #64748b; margin-bottom: 14px; font-size: 9px; }
        table.data { width: 100%; border-collapse: collapse; }
        @include('partials.report-pdf-data-table-styles', ['pdfThPadding' => '6px 5px', 'pdfThFontSize' => '9px'])
        table.data td { padding: 5px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .footer { margin-top: 14px; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
    @php
        $roleLabel = filled($filters['role'] ?? null)
            ? role_label($filters['role'])
            : __('admin.role_all');
        $statusLabel = match ($filters['status'] ?? null) {
            'active' => __('common.active'),
            'inactive' => __('common.inactive'),
            default => __('admin.status_all'),
        };
    @endphp
    <h1>{{ __('nav.users') }}</h1>
    <p class="meta">
        {{ __('admin.search') }}: {{ $filters['search'] ?: '—' }}
        &nbsp;|&nbsp;
        {{ __('common.roles') }}: {{ $roleLabel }}
        &nbsp;|&nbsp;
        {{ __('common.status') }}: {{ $statusLabel }}
        &nbsp;|&nbsp;
        {{ __('admin.total_records') }}: {{ $rows->count() }}
        &nbsp;|&nbsp;
        {{ __('reports.generated_at') }}: {{ now()->translatedFormat('d M Y H:i') }}
    </p>

    <table class="data">
        <thead>
            <tr>
                <th>{{ __('admin.check_number') }}</th>
                <th>{{ __('common.name') }}</th>
                <th>{{ __('common.email') }}</th>
                <th>{{ __('common.phone') }}</th>
                <th>{{ __('common.roles') }}</th>
                <th>{{ __('common.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['check_number'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['email'] }}</td>
                    <td>{{ $row['phone'] }}</td>
                    <td>{{ $row['roles'] }}</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">{{ __('admin.no_users') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">{{ __('nav.welcome') }}</p>
</body>
</html>
