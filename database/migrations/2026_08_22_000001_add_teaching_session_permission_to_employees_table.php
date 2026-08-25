<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('can_submit_teaching_sessions')->default(false)->after('employee_type');
        });

        DB::table('employees')
            ->where(function ($query) {
                $query->where('employee_type', 'part_time')
                    ->orWhereRaw("LOWER(position) LIKE '%teacher%'")
                    ->orWhereRaw("LOWER(position) LIKE '%guru%'");
            })
            ->update(['can_submit_teaching_sessions' => true]);
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('can_submit_teaching_sessions');
        });
    }
};
