<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part_time_schedules', function (Blueprint $table) {
            $table->date('tanggal')->nullable()->after('employee_id');
        });

        DB::statement("ALTER TABLE part_time_schedules MODIFY day_of_week ENUM('senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu') NULL");

        Schema::table('part_time_schedules', function (Blueprint $table) {
            $table->index('employee_id');
            $table->dropUnique(['employee_id', 'day_of_week', 'start_time']);
            $table->unique(['employee_id', 'tanggal', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::table('part_time_schedules', function (Blueprint $table) {
            $table->dropUnique(['employee_id', 'tanggal', 'start_time']);
            $table->unique(['employee_id', 'day_of_week', 'start_time']);
            $table->dropIndex(['employee_id']);
            $table->dropColumn('tanggal');
        });

        DB::statement("ALTER TABLE part_time_schedules MODIFY day_of_week ENUM('senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu') NOT NULL");
    }
};
