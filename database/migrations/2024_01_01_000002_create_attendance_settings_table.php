<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('late_tolerance_minutes')->default(10);
            $table->decimal('late_deduction_per_minute', 12, 2)->default(0);
            $table->decimal('alpa_deduction_per_day', 12, 2)->default(0);
            $table->integer('thr_start_year')->default(2);
            $table->boolean('photo_required')->default(true);
            $table->enum('out_of_radius_policy', ['ditinjau_manual', 'ditolak_otomatis'])
                ->default('ditinjau_manual');
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
