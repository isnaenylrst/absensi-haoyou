<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * PRASYARAT: EmployeeSeeder & ShiftScheduleSeeder harus sudah dijalankan.
     *
     * TODO: isi dari riwayat presensi asli, atau generate ulang di sini
     * berdasarkan data check-in/check-out yang sebenarnya.
     */
    public function run(): void
    {
        $attendances = [
            // [
            //     'employee_code' => 'EMP0000',
            //     'tanggal' => '2026-01-01',
            //     'check_in' => '2026-01-01 08:00:00',
            //     'check_out' => '2026-01-01 17:00:00',
            //     'check_in_lat' => null,
            //     'check_in_lng' => null,
            //     'distance_m' => null,
            //     'status' => 'tepat_waktu',
            //     'late_minutes' => 0,
            // ],
        ];

        foreach ($attendances as $row) {
            $employee = Employee::where('employee_code', $row['employee_code'])
                ->with(['shiftSchedules.shift'])
                ->firstOrFail();

            $tanggal = Carbon::parse($row['tanggal']);
            $dayNames = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
            $day = $dayNames[$tanggal->dayOfWeekIso - 1];
            $shiftSchedule = $employee->shiftSchedules->firstWhere('day_of_week', $day);

            Attendance::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'tanggal' => $tanggal->toDateString(),
                    'shift_id' => $shiftSchedule?->shift_id,
                ],
                [
                    'branch_id' => $employee->branch_id,
                    'shift_schedule_id' => $shiftSchedule?->id,
                    'check_in' => $row['check_in'],
                    'check_out' => $row['check_out'],
                    'check_in_lat' => $row['check_in_lat'] ?? null,
                    'check_in_lng' => $row['check_in_lng'] ?? null,
                    'distance_m' => $row['distance_m'] ?? null,
                    'status' => $row['status'] ?? 'tepat_waktu',
                    'late_minutes' => $row['late_minutes'] ?? 0,
                ]
            );
        }
    }
}