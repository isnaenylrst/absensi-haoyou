<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | PAYROLL COMPONENTS
        |--------------------------------------------------------------------------
        */
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->decimal('bonus_kerajinan', 12, 2)
                ->default(0)
                ->after('allowance');

            $table->decimal('bonus_kinerja', 12, 2)
                ->default(0)
                ->after('bonus_kerajinan');
        });

        /*
        |--------------------------------------------------------------------------
        | PAYSLIPS
        |--------------------------------------------------------------------------
        */
        Schema::table('payslips', function (Blueprint $table) {
            $table->unsignedSmallInteger('hari_efektif')
                ->default(0)
                ->after('period_year');

            $table->decimal('gaji_pokok', 14, 2)
                ->default(0)
                ->after('hari_hadir');

            $table->decimal('uang_makan', 14, 2)
                ->default(0)
                ->after('gaji_pokok');

            $table->decimal('uang_bensin', 14, 2)
                ->default(0)
                ->after('uang_makan');

            $table->decimal('bonus_kerajinan', 14, 2)
                ->default(0)
                ->after('uang_bensin');

            $table->decimal('bonus_kinerja', 14, 2)
                ->default(0)
                ->after('bonus_kerajinan');

            $table->decimal('potongan_telat', 14, 2)
                ->default(0)
                ->after('bonus_kinerja');

            $table->decimal('thr', 14, 2)
                ->default(0)
                ->after('potongan_telat');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropColumn([
                'bonus_kerajinan',
                'bonus_kinerja',
            ]);
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn([
                'hari_efektif',
                'gaji_pokok',
                'uang_makan',
                'uang_bensin',
                'bonus_kerajinan',
                'bonus_kinerja',
                'potongan_telat',
                'thr',
            ]);
        });
    }
};