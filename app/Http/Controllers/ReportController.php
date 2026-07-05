<?php

namespace App\Http\Controllers;

use App\Exports\ApplicationReportsExport;
use App\Exports\ReportsExport;
use App\Services\ApplicationReportService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reports,
        private ApplicationReportService $applicationReports,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('view reports');

        $filters = $this->reports->normalizeFilters($request->all());
        $summary = $this->reports->summary($filters);
        $charts = $this->reports->chartData($filters);
        $rows = $this->reports->paginatedRows($filters);
        $regions = $this->reports->regions();

        return view('reports.index', compact('filters', 'summary', 'charts', 'rows', 'regions'));
    }

    public function applications(Request $request)
    {
        $this->authorize('view reports');

        $filters = $this->applicationReports->normalizeFilters($request->all());
        $rows = $this->applicationReports->paginatedRows($filters);
        $statuses = ApplicationReportService::STATUSES;

        return view('reports.applications.index', compact('filters', 'rows', 'statuses'));
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('view reports');

        $data = $this->exportData($request);

        return Excel::download(
            new ReportsExport($data['summary'], $data['rows'], $data['filters']),
            $this->reports->exportFilename('xlsx')
        );
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('view reports');

        $data = $this->exportData($request);

        return Pdf::loadView('reports.export-pdf', $data)
            ->download($this->reports->exportFilename('pdf'));
    }

    public function exportApplicationsExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('view reports');

        $data = $this->applicationExportData($request);

        return Excel::download(
            new ApplicationReportsExport($data['rows'], $data['filters']),
            $this->applicationReports->exportFilename('xlsx')
        );
    }

    public function exportApplicationsPdf(Request $request)
    {
        $this->authorize('view reports');

        $data = $this->applicationExportData($request);

        return Pdf::loadView('reports.applications.export-pdf', $data)
            ->download($this->applicationReports->exportFilename('pdf'));
    }

    protected function exportData(Request $request): array
    {
        $filters = $this->reports->normalizeFilters($request->all());

        return [
            'filters' => $filters,
            'summary' => $this->reports->summary($filters),
            'rows' => $this->reports->allRows($filters),
        ];
    }

    protected function applicationExportData(Request $request): array
    {
        $filters = $this->applicationReports->normalizeFilters($request->all());

        return [
            'filters' => $filters,
            'rows' => $this->applicationReports->allRows($filters),
        ];
    }
}
