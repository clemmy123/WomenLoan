<?php

namespace App\Http\Controllers;

use App\Services\AdminDashboardService;
use App\Services\AuditLogService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(
        private AdminDashboardService $dashboard,
        private AuditLogService $audits,
    ) {}

    public function index(): View
    {
        $summary = $this->dashboard->summary();
        $usersByRole = $this->dashboard->usersByRole();
        $recentAudit = $this->dashboard->recentAudit();

        return view('admin.dashboard', [
            'summary' => $summary,
            'usersByRole' => $usersByRole,
            'recentAudit' => $recentAudit,
            'audits' => $this->audits,
        ]);
    }
}
