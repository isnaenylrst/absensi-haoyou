<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->integer('hari_hadir')->default(0);
            $table->decimal('total_pendapatan', 14, 2)->default(0);
            $table->decimal('total_potongan', 14, 2)->default(0);
            $table->decimal('total_diterima', 14, 2)->default(0);
            $table->timestamp('published_at')->nullable();

            $table->unique(['employee_id', 'period_month', 'period_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
