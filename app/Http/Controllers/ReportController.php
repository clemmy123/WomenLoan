<?php

namespace App\Http\Controllers;

use App\Exports\AnalyticalDebtExport;
use App\Exports\AnalyticalOverviewExport;
use App\Exports\ApplicationReportsExport;
use App\Exports\ReportsExport;
use App\Services\AnalyticalDebtReportService;
use App\Services\AnalyticalReportService;
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
        private AnalyticalReportService $analyticalReports,
        private AnalyticalDebtReportService $debtReports,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('view reports');

        $filters = $this->reports->normalizeFilters($request->all());
        $summary = $this->reports->summary($filters);
        $charts = $this->reports->chartData($filters);
        $rows = $this->reports->paginatedRows($filters);
        $regions = $this->reports->regions();
        $fiscalYearOptions = $this->reports->fiscalYearOptions();
        $geoBounds = app(\App\Services\GeoHierarchyService::class)->zoneBounds();

        return view('reports.index', compact(
            'filters',
            'summary',
            'charts',
            'rows',
            'regions',
            'fiscalYearOptions',
            'geoBounds',
        ));
    }

    public function applications(Request $request)
    {
        $this->authorize('view reports');

        $filters = $this->applicationReports->normalizeFilters($request->all());
        $rows = $this->applicationReports->paginatedRows($filters);
        $statuses = ApplicationReportService::STATUSES;
        $fiscalYearOptions = $this->applicationReports->fiscalYearOptions();

        return view('reports.applications.index', compact('filters', 'rows', 'statuses', 'fiscalYearOptions'));
    }

    public function analytical()
    {
        $this->authorize('view analytical reports');

        return redirect()->route('reports.analytical.overview');
    }

    public function analyticalOverview(Request $request)
    {
        $this->authorize('view analytical reports');

        $filters = $this->analyticalReports->normalizeFilters($request->all());
        $summary = $this->analyticalReports->summary($filters);
        $charts = $this->analyticalReports->chartData($filters);
        $individuals = $this->analyticalReports->paginatedIndividuals($filters);
        $groups = $this->analyticalReports->paginatedGroups($filters);
        $regions = $this->analyticalReports->regions();
        $sortOptions = $this->analyticalReports->sortOptions();
        $fiscalYearOptions = $this->analyticalReports->fiscalYearOptions();
        $geoBounds = app(\App\Services\GeoHierarchyService::class)->zoneBounds();

        return view('reports.analytical.overview', compact(
            'filters',
            'summary',
            'charts',
            'individuals',
            'groups',
            'regions',
            'sortOptions',
            'fiscalYearOptions',
            'geoBounds',
        ));
    }

    public function analyticalOutstanding(Request $request)
    {
        return $this->analyticalDebtPage($request, AnalyticalDebtReportService::MODE_OUTSTANDING);
    }

    public function analyticalOverdue(Request $request)
    {
        return $this->analyticalDebtPage($request, AnalyticalDebtReportService::MODE_OVERDUE);
    }

    public function exportAnalyticalOutstandingExcel(Request $request): BinaryFileResponse
    {
        return $this->exportAnalyticalDebtExcel($request, AnalyticalDebtReportService::MODE_OUTSTANDING);
    }

    public function exportAnalyticalOutstandingPdf(Request $request)
    {
        return $this->exportAnalyticalDebtPdf($request, AnalyticalDebtReportService::MODE_OUTSTANDING);
    }

    public function exportAnalyticalOverdueExcel(Request $request): BinaryFileResponse
    {
        return $this->exportAnalyticalDebtExcel($request, AnalyticalDebtReportService::MODE_OVERDUE);
    }

    public function exportAnalyticalOverduePdf(Request $request)
    {
        return $this->exportAnalyticalDebtPdf($request, AnalyticalDebtReportService::MODE_OVERDUE);
    }

    protected function analyticalDebtPage(Request $request, string $mode)
    {
        $this->authorize('view analytical reports');

        $filters = $this->debtReports->normalizeFilters($request->all());
        $summary = $this->debtReports->summary($filters, $mode);
        $rows = $this->debtReports->paginatedRows($filters, $mode);
        $regions = $this->debtReports->regions();
        $sortOptions = $this->debtReports->sortOptions();
        $fiscalYearOptions = $this->debtReports->fiscalYearOptions();
        $debtReports = $this->debtReports;
        $geoBounds = app(\App\Services\GeoHierarchyService::class)->zoneBounds();

        $isOverdue = $mode === AnalyticalDebtReportService::MODE_OVERDUE;

        return view('reports.analytical.debts', [
            'mode' => $mode,
            'filters' => $filters,
            'summary' => $summary,
            'rows' => $rows,
            'regions' => $regions,
            'sortOptions' => $sortOptions,
            'fiscalYearOptions' => $fiscalYearOptions,
            'debtReports' => $debtReports,
            'geoBounds' => $geoBounds,
            'pageTitle' => __($isOverdue ? 'analytical_reports.overdue_title' : 'analytical_reports.outstanding_title'),
            'pageSubtitle' => __($isOverdue ? 'analytical_reports.overdue_subtitle' : 'analytical_reports.outstanding_subtitle'),
            'listTitle' => __($isOverdue ? 'analytical_reports.overdue_list' : 'analytical_reports.outstanding_list'),
            'indexRouteName' => $isOverdue ? 'reports.analytical.overdue' : 'reports.analytical.outstanding',
            'excelRouteName' => $isOverdue ? 'reports.analytical.overdue.export.excel' : 'reports.analytical.outstanding.export.excel',
            'pdfRouteName' => $isOverdue ? 'reports.analytical.overdue.export.pdf' : 'reports.analytical.outstanding.export.pdf',
        ]);
    }

    protected function exportAnalyticalDebtExcel(Request $request, string $mode): BinaryFileResponse
    {
        $this->authorize('view analytical reports');
        $data = $this->analyticalDebtExportData($request, $mode);

        return Excel::download(
            new AnalyticalDebtExport($mode, $data['summary'], $data['rows'], $data['filters']),
            $this->debtReports->exportFilename($mode, 'xlsx')
        );
    }

    protected function exportAnalyticalDebtPdf(Request $request, string $mode)
    {
        $this->authorize('view analytical reports');
        $data = $this->analyticalDebtExportData($request, $mode);
        $isOverdue = $mode === AnalyticalDebtReportService::MODE_OVERDUE;

        return Pdf::loadView('reports.analytical.debt-export-pdf', [
            'title' => __($isOverdue ? 'analytical_reports.overdue_title' : 'analytical_reports.outstanding_title'),
            'summary' => $data['summary'],
            'rows' => $data['rows'],
            'filters' => $data['filters'],
        ])
            ->setPaper('a4', 'landscape')
            ->download($this->debtReports->exportFilename($mode, 'pdf'));
    }

    protected function analyticalDebtExportData(Request $request, string $mode): array
    {
        $filters = $this->debtReports->normalizeFilters($request->all());

        return [
            'filters' => $filters,
            'summary' => $this->debtReports->summary($filters, $mode),
            'rows' => $this->debtReports->allRows($filters, $mode),
        ];
    }

    public function exportAnalyticalExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('view analytical reports');

        $data = $this->analyticalExportData($request);

        return Excel::download(
            new AnalyticalOverviewExport(
                $data['summary'],
                $data['individuals'],
                $data['groups'],
                $data['filters'],
            ),
            $this->analyticalReports->exportFilename('xlsx')
        );
    }

    public function exportAnalyticalPdf(Request $request)
    {
        $this->authorize('view analytical reports');

        $data = $this->analyticalExportData($request);

        return Pdf::loadView('reports.analytical.export-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download($this->analyticalReports->exportFilename('pdf'));
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

    protected function analyticalExportData(Request $request): array
    {
        $filters = $this->analyticalReports->normalizeFilters($request->all());

        return [
            'filters' => $filters,
            'summary' => $this->analyticalReports->summary($filters),
            'individuals' => $this->analyticalReports->allIndividualRows($filters),
            'groups' => $this->analyticalReports->allGroupRows($filters),
        ];
    }
}
