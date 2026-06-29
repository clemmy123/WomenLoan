<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\LoanGroupController;
use App\Http\Controllers\LoanApplicationController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\CouncilController;
use App\Http\Controllers\WardController;
use App\Http\Controllers\StreetController;
use App\Http\Controllers\DashboardController;                       


/*
|--------------------------------------------------------------------------
| ROOT REDIRECT
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('applicants.index');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| APPLICANTS MODULE
|--------------------------------------------------------------------------
*/
Route::resource('applicants', ApplicantController::class);

Route::post('applicants/{applicant}/attach-group', [ApplicantController::class, 'attachGroup'])
    ->name('applicants.attach-group');

Route::delete('applicants/{applicant}/detach-group/{group}', [ApplicantController::class, 'detachGroup'])
    ->name('applicants.detach-group');

/*
|--------------------------------------------------------------------------
| LOAN GROUPS MODULE
|--------------------------------------------------------------------------
*/
Route::resource('loan-groups', LoanGroupController::class);

/*
|--------------------------------------------------------------------------
| LOAN APPLICATION MODULE (WIZARD SYSTEM)
|--------------------------------------------------------------------------
*/
// Middleware removed to allow development without a login system
Route::prefix('loan-applications')->name('loan-applications.')->group(function () {
    
    // Index: List of applications and drafts
    Route::get('/', [LoanApplicationController::class, 'index'])->name('index');

    // Create: The Wizard Form
    Route::get('/apply', [LoanApplicationController::class, 'create'])->name('create');

    // Store: Handle form submission and drafting
    Route::post('/store', [LoanApplicationController::class, 'store'])->name('store');
    

    // Show: View individual application
    Route::get('/{id}', [LoanApplicationController::class, 'show'])->name('show');
    Route::post('/save-draft/{id?}', [LoanApplicationController::class, 'saveDraft'])->name('save-draft');
    Route::post('/finalize/{id}', [LoanApplicationController::class, 'finalizeApplication'])->name('finalize');
});

/*
|--------------------------------------------------------------------------
| LOAN AJAX API (DYNAMIC DROPDOWNS)
|--------------------------------------------------------------------------
*/
// Middleware removed to allow AJAX calls to work during development
Route::prefix('api/loans')->name('loans.api.')->group(function () {
    
    // Location cascading dropdowns
    Route::get('/districts/{regionId}', [RegionController::class, 'getDistricts'])->name('districts');

    Route::get('/councils/{districtId}', [CouncilController::class, 'getCouncils'])->name('councils');
    Route::get('/wards/{councilId}', [WardController::class, 'getWards'])->name('wards');
    Route::get('/streets/{wardId}', [StreetController::class, 'getStreets'])->name('streets');

    // Lookups
    Route::get('/applicant/{nin}', [LoanApplicationController::class, 'getApplicantByNin'])->name('applicant');
    Route::get('/group/{groupId}/members', [LoanApplicationController::class, 'getGroupMembers'])->name('group-members');
});