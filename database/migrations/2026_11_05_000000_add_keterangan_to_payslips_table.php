<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            // Nominal bonus kerajinan sebelum dipotong (input awal Owner)
            $table->decimal('bonus_kerajinan_awal', 14, 2)->default(0)->after('bonus_kerajinan');

            // Tanggal (angka hari) saat terlambat, mis. [3, 10]. NULL = slip lama
            $table->json('tanggal_terlambat')->nullable()->after('thr');

            // Tanggal merah di periode itu, mis. [{"tgl":17,"keterangan":"HUT RI"}]
            $table->json('tanggal_libur')->nullable()->after('tanggal_terlambat');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['bonus_kerajinan_awal', 'tanggal_terlambat', 'tanggal_libur']);
        });
    }
};