<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaveRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/beranda', DashboardController::class)
        ->name('dashboard');

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

Route::middleware('auth')->group(function () {
    Route::get('/izin', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::post('/izin', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::patch('/izin/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::patch('/izin/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
});