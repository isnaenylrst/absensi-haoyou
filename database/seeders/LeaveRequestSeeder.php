<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeaveRequestSeeder extends Seeder
{
    /**
     * PRASYARAT: EmployeeSeeder & UserSeeder harus sudah dijalankan
     * (approved_by butuh username akun owner).
     */
    public function run(): void
    {
        $owner = User::where('username', 'owner')->firstOrFail();

        // TODO: isi dari riwayat pengajuan izin/cuti asli.
        $leaveRequests = [
            [
                'employee_code' => 'EMP0002',
                'leave_type' => 'sakit',
                'start_date' => '2026-08-03',
                'end_date' => '2026-08-03',
                'duration_days' => 1,
                'reason' => 'Demam',
                'status' => 'disetujui',
                'approved_at' => '2026-08-03 09:00:00',
            ],
            // Tambahkan pengajuan izin/cuti lain di sini...
        ];

        foreach ($leaveRequests as $row) {
            $employee = Employee::where('employee_code', $row['employee_code'])->firstOrFail();

            LeaveRequest::create([
                'employee_id' => $employee->id,
                'approved_by' => $row['status'] !== 'menunggu' ? $owner->id : null,
                'leave_type' => $row['leave_type'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'duration_days' => $row['duration_days'],
                'reason' => $row['reason'],
                'status' => $row['status'],
                'approved_at' => $row['approved_at'] ?? null,
            ]);
        }
    }
}
