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
        $owner = User::where('username', 'owner')->first();

        // TODO: isi dari riwayat pengajuan izin/cuti asli.
        $leaveRequests = [
            // [
            //     'employee_code' => 'EMP0000',
            //     'leave_type' => 'sakit',
            //     'start_date' => '2026-01-01',
            //     'end_date' => '2026-01-01',
            //     'duration_days' => 1,
            //     'reason' => '',
            //     'status' => 'menunggu',
            //     'approved_at' => null,
            // ],
        ];

        foreach ($leaveRequests as $row) {
            $employee = Employee::where('employee_code', $row['employee_code'])->firstOrFail();

            LeaveRequest::create([
                'employee_id' => $employee->id,
                'approved_by' => $row['status'] !== 'menunggu' ? $owner?->id : null,
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