<?php

namespace App\Http\Controllers;

use App\Services\AdminDashboardService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(
        private AdminDashboardService $dashboard,
    ) {}

    public function index(): View
    {
        $summary = $this->dashboard->summary();
        $usersByRole = $this->dashboard->usersByRole();
        $auditSeries = $this->dashboard->auditActivitySeries(7);

        $adminChartData = [
            'roles' => [
                'labels' => $usersByRole->pluck('label')->values()->all(),
                'data' => $usersByRole->pluck('count')->values()->all(),
                'label' => __('admin.dashboard_user_count'),
            ],
            'audit' => [
                'labels' => $auditSeries['labels'],
                'data' => $auditSeries['data'],
                'label' => __('admin.dashboard_audit_activity'),
            ],
            'canViewAudit' => auth()->user()?->can('view audit logs') ?? false,
        ];

        return view('admin.dashboard', [
            'summary' => $summary,
            'usersByRole' => $usersByRole,
            'adminChartData' => $adminChartData,
        ]);
    }
}
