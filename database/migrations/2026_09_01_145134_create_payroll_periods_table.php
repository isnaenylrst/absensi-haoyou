<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | PERIODE PAYROLL
            |--------------------------------------------------------------------------
            */
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');

            /*
            |--------------------------------------------------------------------------
            | HARI EFEKTIF KERJA
            |--------------------------------------------------------------------------
            | Diinput manual oleh Owner untuk setiap bulan.
            |
            | Contoh:
            | September 2026 = 26 hari
            | Idul Fitri = bisa saja 14 hari
            |--------------------------------------------------------------------------
            */
            $table->unsignedTinyInteger('hari_efektif')
                ->default(0);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | SATU PERIODE HANYA BOLEH SATU DATA
            |--------------------------------------------------------------------------
            */
            $table->unique(
                ['period_year', 'period_month'],
                'payroll_periods_year_month_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};