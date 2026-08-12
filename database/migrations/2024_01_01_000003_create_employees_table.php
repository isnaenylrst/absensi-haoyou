<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('employee_code', 50)->unique();
            $table->string('full_name', 150);
            $table->string('gender', 20)->nullable();
            $table->string('nationality', 50)->nullable();
            $table->string('religion', 50)->nullable();
            $table->string('blood_type', 5)->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('marital_status', ['belum_menikah', 'menikah', 'cerai'])->nullable();
            $table->string('last_education', 30)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->unique()->nullable();
            $table->text('address')->nullable();
            $table->string('position', 100)->nullable();
            $table->enum('employee_type', ['tetap', 'part_time']);
            $table->date('join_date');
            $table->string('nik', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
