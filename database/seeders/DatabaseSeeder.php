<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Master data / fondasi
            BranchesSeeder::class,
            AttendanceSettingSeeder::class,
            ShiftSeeder::class,

            // 2. Karyawan & akun login
            EmployeeSeeder::class,
            UserSeeder::class,

            // 3. Jadwal kerja
            ShiftScheduleSeeder::class,
            PartTimeScheduleSeeder::class,

            // 4. Aktivitas operasional harian
            AttendanceSeeder::class,
            ClientVisitSeeder::class,

            // 5. Payroll & administrasi
            PayrollComponentSeeder::class,
            PayslipSeeder::class,
            ThrRecordSeeder::class,
            LeaveRequestSeeder::class,
        ]);
    }
}
