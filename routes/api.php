<?php

use Illuminate\Support\Facades\Route;
use kayedspace\Doctor\Http\Controllers\Api\MetadataController;
use kayedspace\Doctor\Http\Controllers\Api\ReportsController;
use kayedspace\Doctor\Http\Controllers\Api\ScansController;

Route::post('/scans', [ScansController::class, 'store'])->name('doctor.api.scans.store');
Route::get('/scans/{scanId}', [ScansController::class, 'show'])->name('doctor.api.scans.show');
Route::get('/rules', [MetadataController::class, 'rules'])->name('doctor.api.rules');
Route::get('/capabilities', [MetadataController::class, 'capabilities'])->name('doctor.api.capabilities');
Route::get('/reports', [ReportsController::class, 'index'])->name('doctor.api.reports.index');
Route::get('/reports/{reportId}', [ReportsController::class, 'show'])->name('doctor.api.reports.show');
Route::delete('/reports/{reportId}', [ReportsController::class, 'destroy'])->name('doctor.api.reports.delete');
Route::delete('/reports', [ReportsController::class, 'clear'])->name('doctor.api.reports.clear');
