<?php

namespace App\Http\Controllers;

use App\Services\DashboardStatsService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private DashboardStatsService $stats) {}

    public function index()
    {
        $user = Auth::user();
        $stats = $this->stats->forUser();
        $recentLoans = $this->stats->recentLoans(5);
        $monthly = $this->stats->monthlyApplications();
        $pipeline = $this->stats->stepBreakdown();

        return view('dashboard', compact('user', 'stats', 'recentLoans', 'monthly', 'pipeline'));
    }
}
