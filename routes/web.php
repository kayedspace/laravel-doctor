<?php

use Illuminate\Support\Facades\Route;
use kayedspace\Doctor\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('doctor.dashboard');
Route::post('/scan', [DashboardController::class, 'scan'])->name('doctor.dashboard.scan');
Route::get('/scans/{scanId}', [DashboardController::class, 'status'])->name('doctor.dashboard.status');
Route::get('/reports/{reportId}', [DashboardController::class, 'report'])->name('doctor.dashboard.reports.show');
Route::delete('/reports/{reportId}', [DashboardController::class, 'deleteReport'])->name('doctor.dashboard.reports.delete');
Route::delete('/reports', [DashboardController::class, 'clearReports'])->name('doctor.dashboard.reports.clear');
