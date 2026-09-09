<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('general_reports.title') }}</title>
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
        .footer { margin-top: 16px; font-size: 9px; color: #94a3b8; }
        .sub { font-size: 9px; color: #64748b; }
    </style>
</head>
<body>
    @include('partials.report-pdf-letterhead', [
        'reportTitle' => __('general_reports.title'),
    ])
    <x-report-pdf-meta-bar>
        {{ __('general_reports.loan_type') }}: {{ $typeLabel ?: __('general_reports.all_types') }}
        &nbsp;|&nbsp;
        {{ __('reports.period') }}: {{ __('reports.period_'.($filters['period'] ?? 'annually')) }}
        &nbsp;|&nbsp;
        {{ __('general_reports.generated_at') }}: {{ format_app_datetime() }}
    </x-report-pdf-meta-bar>

    <table class="summary">
        <tr>
            <td>{{ __('general_reports.women_count') }}</td>
            <td>{{ number_format($summary['women_count']) }}</td>
        </tr>
        @if(($viewMode ?? 'all') === 'all')
            <tr>
                <td>{{ __('general_reports.individual_count') }}</td>
                <td>{{ number_format($summary['individual_count']) }}</td>
            </tr>
            <tr>
                <td>{{ __('general_reports.group_count') }}</td>
                <td>{{ number_format($summary['group_count']) }}</td>
            </tr>
            <tr>
                <td>{{ __('general_reports.group_members_count') }}</td>
                <td>{{ number_format($summary['group_members_count']) }}</td>
            </tr>
        @elseif(($viewMode ?? 'all') === 'group')
            <tr>
                <td>{{ __('general_reports.group_count') }}</td>
                <td>{{ number_format($summary['group_count']) }}</td>
            </tr>
            <tr>
                <td>{{ __('general_reports.group_members_count') }}</td>
                <td>{{ number_format($summary['group_members_count']) }}</td>
            </tr>
        @endif
        <tr>
            <td>{{ __('general_reports.bucket_applied') }}</td>
            <td>{{ number_format($summary['applied'] ?? 0) }}</td>
        </tr>
        <tr>
            <td>{{ __('general_reports.bucket_received') }}</td>
            <td>{{ number_format($summary['received'] ?? 0) }}</td>
        </tr>
        <tr>
            <td>{{ __('general_reports.bucket_processing') }}</td>
            <td>{{ number_format($summary['processing'] ?? 0) }}</td>
        </tr>
        <tr>
            <td>{{ __('general_reports.bucket_missed') }}</td>
            <td>{{ number_format($summary['missed'] ?? 0) }}</td>
        </tr>
    </table>

    @php $view = $viewMode ?? 'all'; @endphp
    <table class="data">
        <thead>
            <tr>
                @if($view === 'group')
                    <th>{{ __('general_reports.col_group') }}</th>
                    <th>{{ __('general_reports.col_members') }}</th>
                @else
                    <th>{{ __('general_reports.col_name') }}</th>
                    @if($view === 'all')
                        <th>{{ __('general_reports.col_status') }}</th>
                    @endif
                    <th>{{ __('general_reports.col_account') }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                @if($view === 'group')
                    <td>
                        {{ $row['group_name'] }}
                        <div class="sub">{{ $row['track_id'] }}</div>
                    </td>
                    <td>{{ $row['members_label'] }}</td>
                @elseif($view === 'individual')
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['account_number'] }}</td>
                @else
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['loan_type_label'] }}</td>
                    <td>{{ $row['account_number'] }}</td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ $view === 'all' ? 3 : 2 }}">{{ __('general_reports.no_results') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">{{ __('reports.pdf_fund') }} — {{ __('general_reports.title') }}</p>
</body>
</html>
