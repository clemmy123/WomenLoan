<?php

namespace App\Http\Controllers;

use App\Services\DashboardStatsService;
use App\Services\LoanQueryService;

class ReportController extends Controller
{
    public function __construct(
        private DashboardStatsService $stats,
        private LoanQueryService $loans,
    ) {}

    public function index()
    {
        $this->authorize('view reports');

        $stats = $this->stats->forUser();
        $monthly = $this->stats->monthlyApplications();
        $disbursements = $this->stats->monthlyDisbursements();
        $pipeline = $this->stats->stepBreakdown();
        $statusChart = $this->stats->statusBreakdown();
        $regionChart = $this->stats->byRegion();
        $loans = $this->loans->paginatedForReports();

        return view('reports.index', compact(
            'stats', 'monthly', 'disbursements', 'pipeline',
            'statusChart', 'regionChart', 'loans'
        ));
    }
}
