<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ApplicantGroupController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CouncilController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\LoanApplicationController;
use App\Http\Controllers\LoanGroupController;
use App\Http\Controllers\LoanPaymentController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfilePasswordController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SecureFileController;
use App\Http\Controllers\StreetController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WardController;
use App\Http\Controllers\NidaController;
use App\Http\Controllers\WorkflowController;
use App\Services\LandingStatsService;
use App\Support\AccessibleHome;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::middleware('guest')->group(function () {
    Route::middleware('throttle:10,1')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register']);

        Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
    });
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['guest', 'nida.registration', 'throttle:10,1'])->prefix('api/nida')->name('nida.api.')->group(function () {
    Route::post('/start', [NidaController::class, 'start'])->name('start');
    Route::post('/answer', [NidaController::class, 'answer'])->name('answer');
});

Route::get('/', function (LandingStatsService $landingStats) {
    if (! auth()->check()) {
        return view('home', [
            'landingStats' => $landingStats->totals(),
        ]);
    }

    return redirect()->to(AccessibleHome::url(auth()->user()));
})->name('home');

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('/secure-files/{path}', [SecureFileController::class, 'show'])
        ->where('path', '[A-Za-z0-9+/_=-]+')
        ->name('secure-files.show');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('can:view dashboard')
        ->name('dashboard');

    Route::get('/profile/password', [ProfilePasswordController::class, 'edit'])->name('profile.password.edit');
    Route::put('/profile/password', [ProfilePasswordController::class, 'update'])->name('profile.password.update');
    Route::get('/profile/password/required', [ProfilePasswordController::class, 'editRequired'])->name('profile.password.required');
    Route::put('/profile/password/required', [ProfilePasswordController::class, 'updateRequired'])->name('profile.password.required.update');

    Route::get('/track', [WorkflowController::class, 'track'])
        ->middleware('can:view loan by track id')
        ->name('loans.track');
    Route::post('/loans/{loan}/workflow', [WorkflowController::class, 'action'])->name('loans.workflow');

    Route::resource('applicants', ApplicantController::class);
    Route::post('applicants/{applicant}/attach-group', [ApplicantController::class, 'attachGroup'])->name('applicants.attach-group');
    Route::delete('applicants/{applicant}/detach-group/{group}', [ApplicantController::class, 'detachGroup'])->name('applicants.detach-group');

    Route::resource('loan-groups', LoanGroupController::class)
        ->middleware('can:manage loan groups');

    Route::prefix('my-group')->name('my-group.')->middleware('can:create loan application')->group(function () {
        Route::get('/', [ApplicantGroupController::class, 'show'])->name('show');
        Route::get('/setup', [ApplicantGroupController::class, 'create'])->name('create');
        Route::post('/setup', [ApplicantGroupController::class, 'store'])->name('store');
        Route::post('/members', [ApplicantGroupController::class, 'storeMember'])->name('members.store');
        Route::put('/members/{member}', [ApplicantGroupController::class, 'updateMember'])->name('members.update');
        Route::delete('/members/{member}', [ApplicantGroupController::class, 'destroyMember'])->name('members.destroy');
    });

    Route::prefix('loan-applications')->name('loan-applications.')->group(function () {
        Route::get('/', [LoanApplicationController::class, 'index'])->name('index');
        Route::get('/apply', [LoanApplicationController::class, 'create'])->name('create');
        Route::post('/store', [LoanApplicationController::class, 'store'])->name('store');
        Route::get('/{loan}/edit', [LoanApplicationController::class, 'edit'])->name('edit');
        Route::put('/{loan}', [LoanApplicationController::class, 'update'])->name('update');
        Route::get('/{loan}', [LoanApplicationController::class, 'show'])->name('show');
        Route::post('/save-draft/{id?}', [LoanApplicationController::class, 'saveDraft'])
            ->middleware('throttle:60,1')
            ->name('save-draft');
        Route::post('/finalize/{loan}', [LoanApplicationController::class, 'finalizeApplication'])->name('finalize');
    });

    Route::get('/repayments', [LoanPaymentController::class, 'index'])
        ->middleware('can:view repayments')
        ->name('repayments.index');
    Route::get('/repayments/export/excel', [LoanPaymentController::class, 'exportExcel'])
        ->middleware('can:view repayments')
        ->name('repayments.export.excel');
    Route::get('/repayments/export/pdf', [LoanPaymentController::class, 'exportPdf'])
        ->middleware('can:view repayments')
        ->name('repayments.export.pdf');
    Route::get('/repayments/{payment}', [LoanPaymentController::class, 'show'])
        ->middleware('can:view repayments')
        ->name('repayments.show');
    Route::get('/repayments/{payment}/receipt/{transaction}', [LoanPaymentController::class, 'receipt'])
        ->middleware('can:view repayments')
        ->name('repayments.receipt');
    Route::post('/repayments/{payment}/pay', [LoanPaymentController::class, 'pay'])
        ->middleware('can:record repayment')
        ->name('repayments.pay');
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('can:view reports overview')
        ->name('reports.index');
    Route::get('/reports/applications', [ReportController::class, 'applications'])
        ->middleware('can:view application reports')
        ->name('reports.applications.index');
    Route::get('/reports/applications/export/excel', [ReportController::class, 'exportApplicationsExcel'])
        ->middleware('can:view application reports')
        ->name('reports.applications.export.excel');
    Route::get('/reports/applications/export/pdf', [ReportController::class, 'exportApplicationsPdf'])
        ->middleware('can:view application reports')
        ->name('reports.applications.export.pdf');
    Route::get('/reports/by-region', [ReportController::class, 'byRegion'])
        ->middleware('can:view by region reports')
        ->name('reports.by-region.index');
    Route::get('/reports/by-region/export/excel', [ReportController::class, 'exportByRegionExcel'])
        ->middleware('can:view by region reports')
        ->name('reports.by-region.export.excel');
    Route::get('/reports/by-region/export/pdf', [ReportController::class, 'exportByRegionPdf'])
        ->middleware('can:view by region reports')
        ->name('reports.by-region.export.pdf');
    Route::get('/reports/by-type', [ReportController::class, 'byType'])
        ->middleware('can:view by type reports')
        ->name('reports.by-type.index');
    Route::get('/reports/by-type/export/excel', [ReportController::class, 'exportByTypeExcel'])
        ->middleware('can:view by type reports')
        ->name('reports.by-type.export.excel');
    Route::get('/reports/by-type/export/pdf', [ReportController::class, 'exportByTypePdf'])
        ->middleware('can:view by type reports')
        ->name('reports.by-type.export.pdf');
    Route::get('/reports/by-sector', [ReportController::class, 'bySector'])
        ->middleware('can:view by sector reports')
        ->name('reports.by-sector.index');
    Route::get('/reports/by-sector/export/excel', [ReportController::class, 'exportBySectorExcel'])
        ->middleware('can:view by sector reports')
        ->name('reports.by-sector.export.excel');
    Route::get('/reports/by-sector/export/pdf', [ReportController::class, 'exportBySectorPdf'])
        ->middleware('can:view by sector reports')
        ->name('reports.by-sector.export.pdf');
    Route::get('/reports/by-bank', [ReportController::class, 'byBank'])
        ->middleware('can:view by bank reports')
        ->name('reports.by-bank.index');
    Route::get('/reports/by-bank/export/excel', [ReportController::class, 'exportByBankExcel'])
        ->middleware('can:view by bank reports')
        ->name('reports.by-bank.export.excel');
    Route::get('/reports/by-bank/export/pdf', [ReportController::class, 'exportByBankPdf'])
        ->middleware('can:view by bank reports')
        ->name('reports.by-bank.export.pdf');
    Route::get('/reports/by-monthly', [ReportController::class, 'byMonthly'])
        ->middleware('can:view by monthly reports')
        ->name('reports.by-monthly.index');
    Route::get('/reports/by-monthly/export/excel', [ReportController::class, 'exportByMonthlyExcel'])
        ->middleware('can:view by monthly reports')
        ->name('reports.by-monthly.export.excel');
    Route::get('/reports/by-monthly/export/pdf', [ReportController::class, 'exportByMonthlyPdf'])
        ->middleware('can:view by monthly reports')
        ->name('reports.by-monthly.export.pdf');
    Route::get('/reports/by-age', [ReportController::class, 'byAge'])
        ->middleware('can:view by age reports')
        ->name('reports.by-age.index');
    Route::get('/reports/by-age/export/excel', [ReportController::class, 'exportByAgeExcel'])
        ->middleware('can:view by age reports')
        ->name('reports.by-age.export.excel');
    Route::get('/reports/by-age/export/pdf', [ReportController::class, 'exportByAgePdf'])
        ->middleware('can:view by age reports')
        ->name('reports.by-age.export.pdf');
    Route::get('/reports/analytical', [ReportController::class, 'analytical'])
        ->middleware('can:view payment reports')
        ->name('reports.analytical.index');
    Route::get('/reports/analytical/overview', [ReportController::class, 'analyticalOverview'])
        ->middleware('can:view payment reports')
        ->name('reports.analytical.overview');
    Route::get('/reports/analytical/outstanding', [ReportController::class, 'analyticalOutstanding'])
        ->middleware('can:view outstanding reports')
        ->name('reports.analytical.outstanding');
    Route::get('/reports/analytical/overdue', [ReportController::class, 'analyticalOverdue'])
        ->middleware('can:view overdue reports')
        ->name('reports.analytical.overdue');
    Route::get('/reports/analytical/export/excel', [ReportController::class, 'exportAnalyticalExcel'])
        ->middleware('can:view payment reports')
        ->name('reports.analytical.export.excel');
    Route::get('/reports/analytical/export/pdf', [ReportController::class, 'exportAnalyticalPdf'])
        ->middleware('can:view payment reports')
        ->name('reports.analytical.export.pdf');
    Route::get('/reports/analytical/outstanding/export/excel', [ReportController::class, 'exportAnalyticalOutstandingExcel'])
        ->middleware('can:view outstanding reports')
        ->name('reports.analytical.outstanding.export.excel');
    Route::get('/reports/analytical/outstanding/export/pdf', [ReportController::class, 'exportAnalyticalOutstandingPdf'])
        ->middleware('can:view outstanding reports')
        ->name('reports.analytical.outstanding.export.pdf');
    Route::get('/reports/analytical/overdue/export/excel', [ReportController::class, 'exportAnalyticalOverdueExcel'])
        ->middleware('can:view overdue reports')
        ->name('reports.analytical.overdue.export.excel');
    Route::get('/reports/analytical/overdue/export/pdf', [ReportController::class, 'exportAnalyticalOverduePdf'])
        ->middleware('can:view overdue reports')
        ->name('reports.analytical.overdue.export.pdf');
    Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])
        ->middleware('can:view reports overview')
        ->name('reports.export.excel');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])
        ->middleware('can:view reports overview')
        ->name('reports.export.pdf');

    Route::prefix('admin')->name('admin.')->middleware('can:view administration dashboard')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    });

    Route::prefix('admin')->name('admin.')->middleware('can:manage users')->group(function () {
        Route::get('users/export/excel', [UserController::class, 'exportExcel'])->name('users.export.excel');
        Route::get('users/export/pdf', [UserController::class, 'exportPdf'])->name('users.export.pdf');
        Route::get('users/inactive', [UserController::class, 'inactive'])->name('users.inactive');
        Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::get('users/{user}/assign-roles', [UserController::class, 'assignRoles'])->name('users.assign-roles');
        Route::put('users/{user}/assign-roles', [UserController::class, 'updateRoles'])->name('users.assign-roles.update');
        Route::resource('users', UserController::class);
    });

    Route::prefix('admin')->name('admin.')->middleware('can:manage roles')->group(function () {
        Route::resource('roles', RoleController::class)->except(['show']);
    });

    Route::prefix('admin')->name('admin.')->middleware('can:view audit logs')->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('audit-logs/export/excel', [AuditLogController::class, 'exportExcel'])->name('audit.export.excel');
        Route::get('audit-logs/export/pdf', [AuditLogController::class, 'exportPdf'])->name('audit.export.pdf');
        Route::get('audit-logs/{activity}', [AuditLogController::class, 'show'])->name('audit.show');
    });

    Route::prefix('api/loans')->name('loans.api.')->group(function () {
        Route::get('/districts/{regionId}', [RegionController::class, 'getDistricts'])->name('districts');
        Route::get('/councils/{districtId}', [CouncilController::class, 'getCouncils'])->name('councils');
        Route::get('/wards/{councilId}', [WardController::class, 'getWards'])->name('wards');
        Route::get('/streets/{wardId}', [StreetController::class, 'getStreets'])->name('streets');
        Route::get('/applicant/{nin}', [LoanApplicationController::class, 'getApplicantByNin'])->name('applicant');
        Route::get('/group/{groupId}/members', [LoanApplicationController::class, 'getGroupMembers'])->name('group-members');
    });
});
