<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\ExpenseController;
use Modules\Finance\Http\Controllers\FinanceController;
use Modules\Finance\Http\Controllers\PaymentController;
use Modules\Finance\Http\Controllers\ReportController;

// Membungkus semua route dengan prefix 'v1' dan middleware auth:sanctum
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // ==========================================
    // 1. ROUTE PEMBAYARAN IURAN (PAYMENTS)
    // ==========================================
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']); // Ambil daftar pembayaran
        Route::post('/pay', [PaymentController::class, 'pay']); // Proses bayar iuran (bisa bulk/tahunan)
        Route::delete('/{id}', [PaymentController::class, 'destroy']); // Batalkan transaksi
    });

    // ==========================================
    // 2. ROUTE PENGELUARAN KAS (EXPENSES)
    // ==========================================
    // Menggunakan apiResource karena ini CRUD standar
    Route::apiResource('expenses', ExpenseController::class);

    // ==========================================
    // 3. ROUTE LAPORAN (REPORTS)
    // ==========================================
    Route::prefix('reports')->group(function () {
        Route::get('/yearly', [ReportController::class, 'getYearlySummary']); // Chart Dashboard
        Route::get('/monthly', [ReportController::class, 'getMonthlyDetail']); // Rincian per bulan
    });

});