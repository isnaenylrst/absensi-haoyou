<?php

namespace Database\Seeders;

use App\Models\AttendanceSetting;
use Illuminate\Database\Seeder;

class AttendanceSettingSeeder extends Seeder 
{
    public function run(): void
    {
        
        AttendanceSetting::updateOrCreate([], [
            // Toleransi keterlambatan sebelum dianggap "terlambat"
            'late_tolerance_minutes' => 15,

            // Potongan gaji per menit keterlambatan (di luar toleransi)
            'late_deduction_per_minute' => 0,

            // Potongan gaji per hari kalau alpa (tidak presensi sama sekali)
            'alpa_deduction_per_day' => 10000,

            // THR mulai berlaku setelah karyawan bekerja X tahun
            'thr_start_year' => 2,

            // Wajib lampirkan foto saat presensi (masuk/pulang)
            'photo_required' => true,

            // Kebijakan kalau presensi dilakukan di luar radius kantor:
            // 'ditinjau_manual'   -> tetap tersimpan, ditandai untuk ditinjau owner
            // 'ditolak_otomatis'  -> sistem langsung menolak presensi
            'out_of_radius_policy' => 'ditinjau_manual',
        ]);
    }
}