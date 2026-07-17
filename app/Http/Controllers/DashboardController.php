<?php

namespace App\Http\Controllers;

use App\Services\DashboardStatsService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private DashboardStatsService $stats) {}

    public function index(\Illuminate\Http\Request $request)
    {
        $user = Auth::user();
        $stats = $this->stats->forUser();
        $recentFilter = $this->stats->normalizeRecentFilter($request->query('recent'));
        $recentSearch = trim((string) $request->query('search', ''));
        $recentSort = $this->stats->normalizeRecentSort($request->query('sort'));
        $recentLoans = $this->stats->paginatedRecentLoans($recentFilter, $recentSearch, $recentSort);
        $recentSortOptions = $this->stats->recentSortOptions();
        $monthly = $this->stats->monthlyApplications();
        $pipeline = $this->stats->stepBreakdown();
        $fiscalYear = $this->stats->currentFiscalYearKey();
        $fiscalYearFrom = $this->stats->currentFiscalYearContext()['from'];

        return view('dashboard', compact(
            'user',
            'stats',
            'recentLoans',
            'recentFilter',
            'recentSearch',
            'recentSort',
            'recentSortOptions',
            'monthly',
            'pipeline',
            'fiscalYear',
            'fiscalYearFrom',
        ));
    }
}
