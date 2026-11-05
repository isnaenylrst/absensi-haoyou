<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nominal bonus kerajinan global (sama untuk semua karyawan tetap)
        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->decimal('bonus_kerajinan', 12, 2)->default(0);
        });

        // Status kunci gaji pokok per karyawan
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->boolean('gaji_locked')->default(false);
        });

        // Data lama yang gaji pokoknya sudah terisi langsung dikunci
        DB::table('payroll_components')
            ->where('base_salary', '>', 0)
            ->update(['gaji_locked' => true]);
    }

    public function down(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->dropColumn('bonus_kerajinan');
        });

        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropColumn('gaji_locked');
        });
    }
};