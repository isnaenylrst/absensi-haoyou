<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Karyawan\PresensiController;
use App\Http\Controllers\Karyawan\ClientVisitController;
use App\Http\Controllers\Owner\JadwalKerjaController;
use App\Http\Controllers\Owner\ApprovalController;
use App\Http\Controllers\Owner\KunjunganController;

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

    // ===== Presensi Karyawan =====
    Route::get('/presensi', PresensiController::class)
        ->name('presensi');

    Route::post('/presensi/check-in', [PresensiController::class, 'checkIn'])
        ->name('presensi.check-in');

    Route::post('/presensi/check-out', [PresensiController::class, 'checkOut'])
        ->name('presensi.check-out');

    // ===== Kunjungan Klien — Karyawan =====
    Route::get('/kunjungan-klien-saya', [ClientVisitController::class, 'index'])
        ->name('kunjungan-klien-saya');
    Route::post('/kunjungan-klien-saya', [ClientVisitController::class, 'store'])
        ->name('kunjungan-klien-saya.store');

    // ===== Edit kontak profil — khusus Owner =====
    Route::middleware('role:owner')->group(function () {
        Route::put('/profil', [ProfileController::class, 'update'])
            ->name('profil.update');

        Route::get('/jadwal-kerja', JadwalKerjaController::class)
            ->name('jadwal-kerja');

        Route::post('/jadwal-kerja/shift', [JadwalKerjaController::class, 'storeShift'])
            ->name('owner.shift.store');

        Route::get('/approval', [ApprovalController::class, 'index'])
            ->name('approval');

        Route::get('/kunjungan-klien', [KunjunganController::class, 'index'])
            ->name('kunjungan-klien');
        Route::patch('/kunjungan-klien/{visit}/status', [KunjunganController::class, 'updateStatus'])
            ->name('kunjungan-klien.update-status');
    });
});

Route::get('/', fn () => redirect()->route('login'));