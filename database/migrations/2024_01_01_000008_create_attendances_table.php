<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_schedule_id')->nullable()
                ->constrained('shift_schedules')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()
                ->constrained('shifts')->nullOnDelete();
            $table->foreignId('part_time_schedule_id')->nullable()
                ->constrained('part_time_schedules')->nullOnDelete();

            $table->string('activity', 150)->nullable();

            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->date('tanggal');
            $table->timestamp('check_in')->nullable();
            $table->timestamp('check_out')->nullable();
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();
            $table->decimal('distance_m', 8, 2)->nullable();
            $table->text('check_in_photo_url')->nullable();
            $table->text('check_out_photo_url')->nullable();
            $table->enum('status', ['tepat_waktu', 'terlambat', 'alpa'])->nullable();
            $table->integer('late_minutes')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['employee_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};