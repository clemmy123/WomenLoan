<?php

namespace App\Http\Controllers;

use App\Exports\AuditLogsExport;
use App\Services\AuditLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AuditLogController extends Controller
{
    public function __construct(private AuditLogService $audits) {}

    public function index(Request $request): View
    {
        $filters = $this->audits->normalizeFilters($request->all());
        $activities = $this->audits->paginate($filters);

        return view('admin.audit.index', [
            'activities' => $activities,
            'filters' => $filters,
            'audits' => $this->audits,
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $filters = $this->audits->normalizeFilters($request->all());
        $rows = $this->audits->exportRows($filters);

        return Excel::download(
            new AuditLogsExport($rows, $filters),
            'wdf-audit-logs-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $filters = $this->audits->normalizeFilters($request->all());
        $rows = $this->audits->exportRows($filters);

        return Pdf::loadView('admin.audit.export-pdf', compact('filters', 'rows'))
            ->download('wdf-audit-logs-'.now()->format('Y-m-d-His').'.pdf');
    }

    public function show(int $activity): View
    {
        $record = $this->audits->find($activity);

        return view('admin.audit.show', [
            'activity' => $record,
            'audits' => $this->audits,
        ]);
    }
}
