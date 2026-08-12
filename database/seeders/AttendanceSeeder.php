<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * PRASYARAT: BranchSeeder & EmployeeSeeder harus sudah dijalankan.
     */
    public function run(): void
    {
        $branch = Branch::where('name', 'Haoyou Educator')->firstOrFail();

        // ============================================================
        // TODO: isi dari data presensi sesungguhnya (rekap manual/import
        // dari sistem lama), bukan data contoh di bawah ini.
        // ============================================================
        $attendances = [
            [
                'employee_code' => 'EMP0001',
                'tanggal' => '2026-08-10',
                'check_in' => '2026-08-10 07:55:00',
                'check_out' => '2026-08-10 16:05:00',
                'status' => 'tepat_waktu',
                'late_minutes' => 0,
            ],
            [
                'employee_code' => 'EMP0002',
                'tanggal' => '2026-08-10',
                'check_in' => '2026-08-10 15:00:00',
                'check_out' => '2026-08-10 17:00:00',
                'status' => 'tepat_waktu',
                'late_minutes' => 0,
                'activity' => 'Mengajar Kelas 5A',
            ],
            // Tambahkan baris presensi lain di sini...
        ];

        foreach ($attendances as $row) {
            $employee = Employee::where('employee_code', $row['employee_code'])->firstOrFail();

            Attendance::create([
                'employee_id' => $employee->id,
                'branch_id' => $branch->id,
                'tanggal' => $row['tanggal'],
                'check_in' => $row['check_in'],
                'check_out' => $row['check_out'],
                'status' => $row['status'],
                'late_minutes' => $row['late_minutes'],
                'activity' => $row['activity'] ?? null,
            ]);
        }
    }
}
