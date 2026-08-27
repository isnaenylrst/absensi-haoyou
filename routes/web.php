<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\PayrollController;
use Illuminate\Support\Facades\Route;

    Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->name('login.store');
});

    Route::middleware('auth')->group(function () {
    Route::get('/beranda', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');



    // ===== Profil & Ganti Password (semua role) =====
    Route::get('/profil', [ProfileController::class, 'edit'])
        ->name('profil.edit');

    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])
        ->name('profil.password');

    // ===== Edit kontak profil — khusus Owner =====
    Route::middleware('role:owner')->group(function () {
        Route::put('/profil', [ProfileController::class, 'update'])
            ->name('profil.update');
    });
});

Route::get('/', fn () => redirect()->route('login'));

 // ===== Fitur izin — khusus karwayan =====

Route::middleware('auth')->group(function () {
    Route::get('/izin', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::post('/izin', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::patch('/izin/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::patch('/izin/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
});
 
 // ===== Lihat gaji saya— khusus karyawan =====

Route::middleware('auth')->group(function () {
    Route::get('/gaji-saya', [PayslipController::class, 'index'])->name('payslips.index');
Route::get('/gaji-saya/unduh-pdf', [PayslipController::class, 'downloadPdf'])->name('payslips.download-pdf');
});

 // ===== Edit gaji — khusus Owner =====

Route::middleware('auth')->group(function () {
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::patch('/payroll/{employee}', [PayrollController::class, 'updateComponent'])->name('payroll.update');
    Route::post('/payroll/publish', [PayrollController::class, 'publishAll'])->name('payroll.publish');
    Route::get('/owner/payroll/riwayat', [PayrollController::class, 'history'])->name('payroll.history');
});
