<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $table->decimal('base_salary', 14, 2)->default(0);
            $table->decimal('meal_rate', 12, 2)->default(0);
            $table->decimal('transport_rate', 12, 2)->default(0);
            $table->decimal('hourly_rate', 12, 2)->default(0);
            $table->decimal('allowance', 12, 2)->default(0);
            $table->boolean('thr_active')->default(false);
            $table->date('effective_date')->default(now());
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_components');
    }
};
