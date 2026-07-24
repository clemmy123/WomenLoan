<?php

namespace App\Http\Controllers;

use App\Exports\AnalyticalDebtExport;
use App\Exports\AnalyticalOverviewExport;
use App\Exports\ApplicationReportsExport;
use App\Exports\ByAgeExport;
use App\Exports\ByBankExport;
use App\Exports\ByMonthlyExport;
use App\Exports\ByRegionExport;
use App\Exports\BySectorExport;
use App\Exports\ByTypeExport;
use App\Exports\ReportsExport;
use App\Services\AnalyticalDebtReportService;
use App\Services\AnalyticalReportService;
use App\Services\ApplicationReportService;
use App\Services\ByAgeReportService;
use App\Services\ByBankReportService;
use App\Services\ByMonthlyReportService;
use App\Services\ByRegionReportService;
use App\Services\BySectorReportService;
use App\Services\ByTypeReportService;
use App\Services\GeoHierarchyService;
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
        private ByRegionReportService $byRegionReports,
        private ByTypeReportService $byTypeReports,
        private BySectorReportService $bySectorReports,
        private ByBankReportService $byBankReports,
        private ByMonthlyReportService $byMonthlyReports,
        private ByAgeReportService $byAgeReports,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('view reports overview');

        $filtersApplied = $request->hasAny(['fiscal_year', 'period', 'date_from', 'date_to']);
        $filters = $this->reports->normalizeFilters($request->all());
        $fiscalYearOptions = $this->reports->fiscalYearOptions();

        $summary = null;
        $charts = null;
        $rows = null;

        if ($filtersApplied) {
            $summary = $this->reports->summary($filters);
            $charts = $this->reports->chartData($filters);
            $rows = $this->reports->paginatedRows($filters);
        }

        return view('reports.index', compact(
            'filters',
            'filtersApplied',
            'summary',
            'charts',
            'rows',
            'fiscalYearOptions',
        ));
    }

    public function applications(Request $request)
    {
        $this->authorize('view application reports');

        $filtersApplied = $request->hasAny([
            'fiscal_year',
            'period',
            'date_from',
            'date_to',
            'status',
            'use_custom_dates',
        ]);
        $filters = $this->applicationReports->normalizeFilters($request->all());
        $statuses = ApplicationReportService::STATUSES;
        $fiscalYearOptions = $this->applicationReports->fiscalYearOptions();
        $rows = $filtersApplied
            ? $this->applicationReports->paginatedRows($filters)
            : null;

        return view('reports.applications.index', compact(
            'filters',
            'filtersApplied',
            'rows',
            'statuses',
            'fiscalYearOptions',
        ));
    }

    public function byRegion(Request $request)
    {
        $this->authorize('view by region reports');

        $filtersApplied = $request->hasAny([
            'fiscal_year',
            'period',
            'date_from',
            'date_to',
            'region_id',
            'district_id',
            'council_id',
            'ward_id',
            'street_id',
            'sort',
        ]);
        $filters = $this->byRegionReports->normalizeFilters($request->all());
        $fiscalYearOptions = $this->byRegionReports->fiscalYearOptions();
        $regions = $this->byRegionReports->regions();
        $sortOptions = $this->byRegionReports->sortOptions();
        $geoBounds = app(GeoHierarchyService::class)->zoneBounds();

        $summary = null;
        $rows = null;

        if ($filtersApplied) {
            $summary = $this->byRegionReports->summary($filters);
            $rows = $this->byRegionReports->paginatedRows($filters);
        }

        return view('reports.by-region.index', compact(
            'filters',
            'filtersApplied',
            'summary',
            'rows',
            'fiscalYearOptions',
            'regions',
            'sortOptions',
            'geoBounds',
        ));
    }

    public function exportByRegionExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('view by region reports');

        $data = $this->byRegionExportData($request);

        return Excel::download(
            new ByRegionExport(
                $data['summary'],
                $data['rows'],
                $data['filters'],
                $data['regionLabel'],
            ),
            $this->byRegionReports->exportFilename('xlsx')
        );
    }

    public function exportByRegionPdf(Request $request)
    {
        $this->authorize('view by region reports');

        $data = $this->byRegionExportData($request);

        return Pdf::loadView('reports.by-region.export-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download($this->byRegionReports->exportFilename('pdf'));
    }

    public function byType(Request $request)
    {
        $this->authorize('view by type reports');

        $filtersApplied = $request->hasAny(['fiscal_year', 'period', 'date_from', 'date_to', 'loan_type', 'sort']);
        $filters = $this->byTypeReports->normalizeFilters($request->all());
        $fiscalYearOptions = $this->byTypeReports->fiscalYearOptions();
        $sortOptions = $this->byTypeReports->sortOptions();

        $summary = null;
        $rows = null;

        if ($filtersApplied) {
            $summary = $this->byTypeReports->summary($filters);
            $rows = $this->byTypeReports->paginatedRows($filters);
        }

        return view('reports.by-type.index', compact(
            'filters',
            'filtersApplied',
            'summary',
            'rows',
            'fiscalYearOptions',
            'sortOptions',
        ));
    }

    public function exportByTypeExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('view by type reports');

        $data = $this->byTypeExportData($request);

        return Excel::download(
            new ByTypeExport(
                $data['summary'],
                $data['rows'],
                $data['filters'],
                $data['typeLabel'],
            ),
            $this->byTypeReports->exportFilename('xlsx')
        );
    }

    public function exportByTypePdf(Request $request)
    {
        $this->authorize('view by type reports');

        $data = $this->byTypeExportData($request);

        return Pdf::loadView('reports.by-type.export-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download($this->byTypeReports->exportFilename('pdf'));
    }

    public function bySector(Request $request)
    {
        $this->authorize('view by sector reports');

        $filtersApplied = $request->hasAny(['fiscal_year', 'period', 'date_from', 'date_to', 'business_sector', 'sort']);
        $filters = $this->bySectorReports->normalizeFilters($request->all());
        $sectors = $this->bySectorReports->sectors();
        $fiscalYearOptions = $this->bySectorReports->fiscalYearOptions();
        $sortOptions = $this->bySectorReports->sortOptions();

        $summary = null;
        $rows = null;

        if ($filtersApplied) {
            $summary = $this->bySectorReports->summary($filters);
            $rows = $this->bySectorReports->paginatedRows($filters);
        }

        return view('reports.by-sector.index', compact(
            'filters',
            'filtersApplied',
            'summary',
            'rows',
            'sectors',
            'fiscalYearOptions',
            'sortOptions',
        ));
    }

    public function exportBySectorExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('view by sector reports');

        $data = $this->bySectorExportData($request);

        return Excel::download(
            new BySectorExport(
                $data['summary'],
                $data['rows'],
                $data['filters'],
                $data['sectorLabel'],
            ),
            $this->bySectorReports->exportFilename('xlsx')
        );
    }

    public function exportBySectorPdf(Request $request)
    {
        $this->authorize('view by sector reports');

        $data = $this->bySectorExportData($request);

        return Pdf::loadView('reports.by-sector.export-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download($this->bySectorReports->exportFilename('pdf'));
    }

    public function byBank(Request $request)
    {
        $this->authorize('view by bank reports');

        $filtersApplied = $request->hasAny(['fiscal_year', 'period', 'date_from', 'date_to', 'bank_name', 'sort']);
        $filters = $this->byBankReports->normalizeFilters($request->all());
        $fiscalYearOptions = $this->byBankReports->fiscalYearOptions();
        $sortOptions = $this->byBankReports->sortOptions();
        $banks = $this->byBankReports->banks();

        $summary = null;
        $rows = null;

        if ($filtersApplied) {
            $summary = $this->byBankReports->summary($filters);
            $rows = $this->byBankReports->paginatedRows($filters);
        }

        return view('reports.by-bank.index', compact(
            'filters',
            'filtersApplied',
            'summary',
            'rows',
            'fiscalYearOptions',
            'sortOptions',
            'banks',
        ));
    }

    public function exportByBankExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('view by bank reports');

        $data = $this->byBankExportData($request);

        return Excel::download(
            new ByBankExport(
                $data['summary'],
                $data['rows'],
                $data['filters'],
                $data['bankLabel'],
            ),
            $this->byBankReports->exportFilename('xlsx')
        );
    }

    public function exportByBankPdf(Request $request)
    {
        $this->authorize('view by bank reports');

        $data = $this->byBankExportData($request);

        return Pdf::loadView('reports.by-bank.export-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download($this->byBankReports->exportFilename('pdf'));
    }

    public function byMonthly(Request $request)
    {
        $this->authorize('view by monthly reports');

        $filtersApplied = $request->hasAny(['month']);
        $filters = $this->byMonthlyReports->normalizeFilters($request->all());
        $monthOptions = $this->byMonthlyReports->monthOptions();
        $reportYear = $filters['year'];

        $summary = null;
        $rows = null;

        if ($filtersApplied) {
            $summary = $this->byMonthlyReports->summary($filters);
            $rows = $this->byMonthlyReports->paginatedRows($filters);
        }

        return view('reports.by-monthly.index', compact(
            'filters',
            'filtersApplied',
            'summary',
            'rows',
            'monthOptions',
            'reportYear',
        ));
    }

    public function exportByMonthlyExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('view by monthly reports');

        $data = $this->byMonthlyExportData($request);

        return Excel::download(
            new ByMonthlyExport(
                $data['summary'],
                $data['rows'],
                $data['filters'],
                $data['monthLabel'],
            ),
            $this->byMonthlyReports->exportFilename('xlsx')
        );
    }

    public function exportByMonthlyPdf(Request $request)
    {
        $this->authorize('view by monthly reports');

        $data = $this->byMonthlyExportData($request);

        return Pdf::loadView('reports.by-monthly.export-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download($this->byMonthlyReports->exportFilename('pdf'));
    }

    public function byAge(Request $request)
    {
        $this->authorize('view by age reports');

        $filtersApplied = $request->hasAny([
            'region_id', 'district_id', 'council_id', 'ward_id', 'street_id',
            'age_min', 'age_max', 'sort',
        ]);
        $filters = $this->byAgeReports->normalizeFilters($request->all());
        $regions = $this->byAgeReports->regions();
        $sortOptions = $this->byAgeReports->sortOptions();
        $geoBounds = app(GeoHierarchyService::class)->zoneBounds();

        $summary = null;
        $rows = null;

        if ($filtersApplied) {
            $summary = $this->byAgeReports->summary($filters);
            $rows = $this->byAgeReports->paginatedRows($filters);
        }

        return view('reports.by-age.index', compact(
            'filters',
            'filtersApplied',
            'summary',
            'rows',
            'regions',
            'sortOptions',
            'geoBounds',
        ));
    }

    public function exportByAgeExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('view by age reports');

        $data = $this->byAgeExportData($request);

        return Excel::download(
            new ByAgeExport(
                $data['summary'],
                $data['rows'],
                $data['filters'],
                $data['regionLabel'],
            ),
            $this->byAgeReports->exportFilename('xlsx')
        );
    }

    public function exportByAgePdf(Request $request)
    {
        $this->authorize('view by age reports');

        $data = $this->byAgeExportData($request);

        return Pdf::loadView('reports.by-age.export-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download($this->byAgeReports->exportFilename('pdf'));
    }

    public function analytical()
    {
        $this->authorize('view payment reports');

        return redirect()->route('reports.analytical.overview');
    }

    public function analyticalOverview(Request $request)
    {
        $this->authorize('view payment reports');

        $filtersApplied = $request->hasAny(['fiscal_year', 'period', 'date_from', 'date_to']);
        $filters = $this->analyticalReports->normalizeFilters($request->all());
        $fiscalYearOptions = $this->analyticalReports->fiscalYearOptions();

        $summary = null;
        $charts = null;
        $individuals = null;
        $groups = null;

        if ($filtersApplied) {
            $summary = $this->analyticalReports->summary($filters);
            $charts = $this->analyticalReports->chartData($filters);
            $individuals = $this->analyticalReports->paginatedIndividuals($filters);
            $groups = $this->analyticalReports->paginatedGroups($filters);
        }

        return view('reports.analytical.overview', compact(
            'filters',
            'filtersApplied',
            'summary',
            'charts',
            'individuals',
            'groups',
            'fiscalYearOptions',
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
        $this->authorize(
            $mode === AnalyticalDebtReportService::MODE_OVERDUE
                ? 'view overdue reports'
                : 'view outstanding reports'
        );

        $filtersApplied = $request->hasAny(['fiscal_year', 'period', 'date_from', 'date_to']);
        $filters = $this->debtReports->normalizeFilters($request->all());
        $fiscalYearOptions = $this->debtReports->fiscalYearOptions();

        $summary = null;
        $rows = null;

        if ($filtersApplied) {
            $summary = $this->debtReports->summary($filters, $mode);
            $rows = $this->debtReports->paginatedRows($filters, $mode);
        }

        $isOverdue = $mode === AnalyticalDebtReportService::MODE_OVERDUE;

        return view('reports.analytical.debts', [
            'mode' => $mode,
            'filters' => $filters,
            'filtersApplied' => $filtersApplied,
            'summary' => $summary,
            'rows' => $rows,
            'fiscalYearOptions' => $fiscalYearOptions,
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
        $this->authorize(
            $mode === AnalyticalDebtReportService::MODE_OVERDUE
                ? 'view overdue reports'
                : 'view outstanding reports'
        );
        $data = $this->analyticalDebtExportData($request, $mode);

        return Excel::download(
            new AnalyticalDebtExport($mode, $data['summary'], $data['rows'], $data['filters']),
            $this->debtReports->exportFilename($mode, 'xlsx')
        );
    }

    protected function exportAnalyticalDebtPdf(Request $request, string $mode)
    {
        $this->authorize(
            $mode === AnalyticalDebtReportService::MODE_OVERDUE
                ? 'view overdue reports'
                : 'view outstanding reports'
        );
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
        $this->authorize('view payment reports');

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
        $this->authorize('view payment reports');

        $data = $this->analyticalExportData($request);

        return Pdf::loadView('reports.analytical.export-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download($this->analyticalReports->exportFilename('pdf'));
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('view reports overview');

        $data = $this->exportData($request);

        return Excel::download(
            new ReportsExport($data['summary'], $data['rows'], $data['filters']),
            $this->reports->exportFilename('xlsx')
        );
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('view reports overview');

        $data = $this->exportData($request);

        return Pdf::loadView('reports.export-pdf', $data)
            ->download($this->reports->exportFilename('pdf'));
    }

    public function exportApplicationsExcel(Request $request): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $this->authorize('view application reports');

        if (! $this->applicationFiltersApplied($request)) {
            return redirect()->route('reports.applications.index');
        }

        $data = $this->applicationExportData($request);

        return Excel::download(
            new ApplicationReportsExport($data['rows'], $data['filters']),
            $this->applicationReports->exportFilename('xlsx')
        );
    }

    public function exportApplicationsPdf(Request $request)
    {
        $this->authorize('view application reports');

        if (! $this->applicationFiltersApplied($request)) {
            return redirect()->route('reports.applications.index');
        }

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

    protected function applicationFiltersApplied(Request $request): bool
    {
        return $request->hasAny([
            'fiscal_year',
            'period',
            'date_from',
            'date_to',
            'status',
            'use_custom_dates',
        ]);
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

    protected function byRegionExportData(Request $request): array
    {
        $filters = $this->byRegionReports->normalizeFilters($request->all());
        $regionLabel = null;

        if (! empty($filters['region_id'])) {
            $regionLabel = $this->byRegionReports->regions()
                ->firstWhere('id', (int) $filters['region_id'])
                ?->name;
        }

        return [
            'filters' => $filters,
            'summary' => $this->byRegionReports->summary($filters),
            'rows' => $this->byRegionReports->allRows($filters),
            'regionLabel' => $regionLabel,
        ];
    }

    protected function byTypeExportData(Request $request): array
    {
        $filters = $this->byTypeReports->normalizeFilters($request->all());
        $typeLabel = null;

        if (! empty($filters['loan_type'])) {
            $typeLabel = loan_type_label($filters['loan_type']);
        }

        return [
            'filters' => $filters,
            'summary' => $this->byTypeReports->summary($filters),
            'rows' => $this->byTypeReports->allRows($filters),
            'typeLabel' => $typeLabel,
        ];
    }

    protected function bySectorExportData(Request $request): array
    {
        $filters = $this->bySectorReports->normalizeFilters($request->all());

        return [
            'filters' => $filters,
            'summary' => $this->bySectorReports->summary($filters),
            'rows' => $this->bySectorReports->allRows($filters),
            'sectorLabel' => $filters['business_sector'] ?? null,
        ];
    }

    protected function byBankExportData(Request $request): array
    {
        $filters = $this->byBankReports->normalizeFilters($request->all());

        return [
            'filters' => $filters,
            'summary' => $this->byBankReports->summary($filters),
            'rows' => $this->byBankReports->allRows($filters),
            'bankLabel' => $filters['bank_name'] ?? null,
        ];
    }

    protected function byMonthlyExportData(Request $request): array
    {
        $filters = $this->byMonthlyReports->normalizeFilters($request->all());
        $monthLabel = null;

        if (! empty($filters['month'])) {
            $monthLabel = __('by_monthly_reports.month_'.(int) $filters['month']);
        }

        return [
            'filters' => $filters,
            'summary' => $this->byMonthlyReports->summary($filters),
            'rows' => $this->byMonthlyReports->allRows($filters),
            'monthLabel' => $monthLabel,
            'reportYear' => $filters['year'],
        ];
    }

    protected function byAgeExportData(Request $request): array
    {
        $filters = $this->byAgeReports->normalizeFilters($request->all());
        $regionLabel = null;

        if (! empty($filters['region_id'])) {
            $regionLabel = $this->byAgeReports->regions()
                ->firstWhere('id', (int) $filters['region_id'])
                ?->name;
        }

        return [
            'filters' => $filters,
            'summary' => $this->byAgeReports->summary($filters),
            'rows' => $this->byAgeReports->allRows($filters),
            'regionLabel' => $regionLabel,
        ];
    }
}
