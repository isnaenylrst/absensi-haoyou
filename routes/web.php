<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Karyawan\PresensiController;
use App\Http\Controllers\Karyawan\ClientVisitController;
use App\Http\Controllers\Owner\EmployeeController;
use App\Http\Controllers\Owner\JadwalKerjaController;
use App\Http\Controllers\Owner\ApprovalController;
use App\Http\Controllers\Owner\KunjunganController;
use App\Http\Controllers\Owner\SettingController;
use Illuminate\Support\Facades\Route;

// ======================================================
// AUTH
// ======================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->name('login.store');
});

// ======================================================
// AUTHENTICATED
// ======================================================
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/beranda', DashboardController::class)
        ->name('dashboard');

    // Logout
    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');


    // ==================================================
    // PROFIL & PASSWORD
    // ==================================================

    // Semua role
    Route::get('/profil', [ProfileController::class, 'edit'])
        ->name('profil.edit');

    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])
        ->name('profil.password');


    // ==================================================
    // PRESENSI KARYAWAN
    // ==================================================

    Route::get('/presensi', PresensiController::class)
        ->name('presensi');

    Route::post('/presensi/check-in', [PresensiController::class, 'checkIn'])
        ->name('presensi.check-in');

    Route::post('/presensi/check-out', [PresensiController::class, 'checkOut'])
        ->name('presensi.check-out');


    // ==================================================
    // KUNJUNGAN KLIEN - KARYAWAN
    // ==================================================

    Route::get('/kunjungan-klien-saya', [ClientVisitController::class, 'index'])
        ->name('kunjungan-klien-saya');

    Route::post('/kunjungan-klien-saya', [ClientVisitController::class, 'store'])
        ->name('kunjungan-klien-saya.store');


    // ==================================================
    // OWNER ONLY
    // ==================================================

    Route::middleware('role:owner')->group(function () {

        // ----------------------------------------------
        // Profil Owner
        // ----------------------------------------------

        Route::put('/profil', [ProfileController::class, 'update'])
            ->name('profil.update');


        // ----------------------------------------------
        // Karyawan
        // ----------------------------------------------

        Route::get('karyawan-export', [EmployeeController::class, 'exportCsv'])
            ->name('karyawan.export');

        Route::get('karyawan-import', [EmployeeController::class, 'importForm'])
            ->name('karyawan.import.form');

        Route::post('karyawan-import', [EmployeeController::class, 'importStore'])
            ->name('karyawan.import.store');

        Route::get('karyawan-import/template', [EmployeeController::class, 'downloadTemplate'])
            ->name('karyawan.import.template');

        Route::resource('karyawan', EmployeeController::class);

        Route::post('karyawan/{karyawan}/reset-password', [EmployeeController::class, 'resetPassword'])
            ->name('karyawan.reset-password');

        Route::post('karyawan/{karyawan}/toggle-status', [EmployeeController::class, 'toggleStatus'])
            ->name('karyawan.toggle-status');


        // ----------------------------------------------
        // Jadwal Kerja
        // ----------------------------------------------

        Route::get('/jadwal-kerja', JadwalKerjaController::class)
            ->name('jadwal-kerja');

        Route::get('/jadwal-kerja/presensi-bulanan/{employee}', [JadwalKerjaController::class, 'presensiBulanan'])
            ->name('jadwal-kerja.presensi-bulanan');

        Route::get('/jadwal-kerja/guru/{employee}/jadwal-bulanan', [JadwalKerjaController::class, 'jadwalGuruBulanan'])
            ->name('jadwal-kerja.guru-bulanan');

        Route::post('/jadwal-kerja/shift', [JadwalKerjaController::class, 'storeShift'])
            ->name('owner.shift.store');


        // ----------------------------------------------
        // Approval Presensi
        // ----------------------------------------------

        Route::get('/approval', [ApprovalController::class, 'index'])
            ->name('approval');


        // ----------------------------------------------
        // Kunjungan Klien - Owner
        // ----------------------------------------------

        Route::get('/kunjungan-klien', [KunjunganController::class, 'index'])
            ->name('kunjungan-klien');

        Route::patch('/kunjungan-klien/{visit}/status', [KunjunganController::class, 'updateStatus'])
            ->name('kunjungan-klien.update-status');


        // ----------------------------------------------
        // Pengaturan
        // ----------------------------------------------

        Route::get('/pengaturan', [SettingController::class, 'edit'])
            ->name('pengaturan.edit');

        Route::put('/pengaturan/lokasi', [SettingController::class, 'updateLokasi'])
            ->name('pengaturan.lokasi');

        Route::put('/pengaturan/aturan', [SettingController::class, 'updateAturan'])
            ->name('pengaturan.aturan');
    });
});


// ======================================================
// ROOT
// ======================================================

Route::get('/', fn () => redirect()->route('login'));