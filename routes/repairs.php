<?php

use App\Http\Controllers\Repairs\RepairCatalogController;
use App\Http\Controllers\Repairs\RepairDashboardController;
use App\Http\Controllers\Repairs\RepairJobController;
use App\Http\Controllers\Repairs\RepairPrintController;
use App\Http\Controllers\Repairs\RepairReportController;
use App\Http\Controllers\Repairs\RepairSettingsController;
use App\Http\Controllers\Repairs\RepairTechnicianController;
use App\Http\Controllers\Repairs\RepairWorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('repairs')->name('repairs.')->middleware(['repair.enabled', 'auth', 'verified'])->group(function () {
    Route::get('/', RepairDashboardController::class)->middleware('permission:repair.view')->name('dashboard');
    Route::get('/jobs', [RepairJobController::class, 'index'])->middleware('permission:repair.view')->name('index');
    Route::get('/jobs/create', [RepairJobController::class, 'create'])->middleware('permission:repair.create')->name('create');
    Route::post('/jobs', [RepairJobController::class, 'store'])->middleware('permission:repair.create')->name('store');
    Route::get('/customers', [RepairJobController::class, 'customers'])->middleware('permission:repair.create|repair.edit')->name('customers');
    Route::post('/customers', [RepairJobController::class, 'quickCustomer'])->middleware('permission:repair.create')->name('customers.store');
    Route::get('/products', [RepairJobController::class, 'products'])->middleware('permission:repair.parts')->name('products');
    Route::get('/technicians', [RepairTechnicianController::class, 'index'])->middleware('permission:repair.technicians|repair.assign')->name('technicians');
    Route::post('/technicians', [RepairTechnicianController::class, 'store'])->middleware('permission:repair.technicians')->name('technicians.store');
    Route::put('/technicians/{technician}', [RepairTechnicianController::class, 'update'])->middleware('permission:repair.technicians')->name('technicians.update');
    Route::get('/technicians/{technician}/photo', [RepairTechnicianController::class, 'photo'])->middleware('permission:repair.view|repair.technicians')->name('technicians.photo');
    Route::get('/settings', [RepairSettingsController::class, 'show'])->middleware('permission:repair.settings')->name('settings');
    Route::put('/settings', [RepairSettingsController::class, 'update'])->middleware('permission:repair.settings')->name('settings.update');
    Route::post('/settings/{catalog}', [RepairCatalogController::class, 'store'])->whereIn('catalog', ['statuses', 'problems', 'charges'])->middleware('permission:repair.settings')->name('catalog.store');
    Route::put('/settings/{catalog}/{record}', [RepairCatalogController::class, 'update'])->whereIn('catalog', ['statuses', 'problems', 'charges'])->middleware('permission:repair.settings')->name('catalog.update');
    Route::get('/reports/{report?}', RepairReportController::class)->whereIn('report', ['jobs', 'revenue', 'parts', 'technicians', 'statuses', 'outstanding'])->middleware('permission:repair.report')->name('reports');
    Route::get('/jobs/{repairJob}', [RepairJobController::class, 'show'])->middleware('permission:repair.view')->name('show');
    Route::get('/jobs/{repairJob}/edit', [RepairJobController::class, 'edit'])->middleware('permission:repair.edit')->name('edit');
    Route::put('/jobs/{repairJob}', [RepairJobController::class, 'update'])->middleware('permission:repair.edit')->name('update');
    Route::delete('/jobs/{repairJob}', [RepairWorkflowController::class, 'destroy'])->middleware('permission:repair.delete')->name('destroy');
    Route::post('/jobs/{repairJob}/credential', [RepairWorkflowController::class, 'reveal'])->middleware(['permission:repair.credentials.view', 'throttle:20,1'])->name('credential');
    Route::get('/jobs/{repairJob}/images/{image}', [RepairWorkflowController::class, 'image'])->middleware('permission:repair.view')->name('image');
    Route::get('/jobs/{repairJob}/job-card', [RepairPrintController::class, 'card'])->middleware('permission:repair.invoice')->name('job-card');
    Route::get('/jobs/{repairJob}/invoice', [RepairPrintController::class, 'invoice'])->middleware('permission:repair.invoice')->name('invoice');
    foreach (['status', 'assign', 'part', 'part-state', 'charge', 'charge-delete', 'pricing', 'payment', 'note', 'approval', 'checklist', 'warranty', 'photo', 'photo-delete'] as $action) {
        Route::post('/jobs/{repairJob}/'.$action, [RepairWorkflowController::class, 'action'])->defaults('action', $action)->name($action);
    }
});
