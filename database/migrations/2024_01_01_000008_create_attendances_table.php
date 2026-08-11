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
            $table->foreignId('part_time_schedule_id')->nullable()
                ->constrained('part_time_schedules')->nullOnDelete();

            // Kegiatan yang diketik manual saat submit presensi (khusus part
            // time, karena tiap sesi bersifat freeform - jam & kegiatan bisa
            // beda dari yang tersimpan di part_time_schedules). NULL untuk
            // karyawan tetap.
            $table->string('activity', 150)->nullable();

            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->date('tanggal');
            $table->timestamp('check_in')->nullable();
            $table->timestamp('check_out')->nullable();
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();
            $table->decimal('distance_m', 8, 2)->nullable();
            $table->text('photo_url')->nullable();
            $table->enum('status', ['tepat_waktu', 'terlambat', 'alpa'])->nullable();
            $table->integer('late_minutes')->default(0);
            $table->timestamp('created_at')->useCurrent();

            // Sengaja TIDAK ada UNIQUE constraint di sini. Presensi karyawan
            // part time bersifat freeform (bisa berkali-kali/hari, jam & sesi
            // diisi manual, tidak selalu terikat 1 baris part_time_schedules).
            // Validasi "1x presensi/hari" untuk karyawan tetap dilakukan di
            // level aplikasi (controller/FormRequest), bukan di database.
            $table->index(['employee_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
