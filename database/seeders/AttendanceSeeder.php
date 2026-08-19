<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::where('name', 'Haoyou Educator')->firstOrFail();

        $senin = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $tgl = fn (int $offsetHari) => $senin->copy()->addDays($offsetHari)->format('Y-m-d');

        // offset hari dari Senin: 0=senin, 1=selasa, 2=rabu, 3=kamis, 4=jumat, 5=sabtu
        $attendances = [
            // ===== Senin =====
            ['employee_code' => 'EMP0001', 'tanggal' => $tgl(0), 'check_in' => $tgl(0).' 07:55:00', 'check_out' => $tgl(0).' 18:05:00', 'status' => 'tepat_waktu', 'late_minutes' => 0],
            ['employee_code' => 'EMP0011', 'tanggal' => $tgl(0), 'check_in' => $tgl(0).' 09:10:00', 'check_out' => $tgl(0).' 18:00:00', 'status' => 'tepat_waktu', 'late_minutes' => 0],
            ['employee_code' => 'EMP0012', 'tanggal' => $tgl(0), 'check_in' => $tgl(0).' 12:05:00', 'check_out' => $tgl(0).' 21:00:00', 'status' => 'tepat_waktu', 'late_minutes' => 0],
            ['employee_code' => 'EMP0010', 'tanggal' => $tgl(0), 'check_in' => $tgl(0).' 12:20:00', 'check_out' => $tgl(0).' 21:00:00', 'status' => 'terlambat', 'late_minutes' => 20],

            // ===== Selasa =====
            ['employee_code' => 'EMP0001', 'tanggal' => $tgl(1), 'check_in' => $tgl(1).' 08:50:00', 'check_out' => $tgl(1).' 18:00:00', 'status' => 'tepat_waktu', 'late_minutes' => 0],
            ['employee_code' => 'EMP0013', 'tanggal' => $tgl(1), 'check_in' => $tgl(1).' 09:00:00', 'check_out' => $tgl(1).' 18:00:00', 'status' => 'tepat_waktu', 'late_minutes' => 0],
            ['employee_code' => 'EMP0015', 'tanggal' => $tgl(1), 'check_in' => $tgl(1).' 12:15:00', 'check_out' => $tgl(1).' 21:00:00', 'status' => 'tepat_waktu', 'late_minutes' => 0, 'activity' => 'Mengajar Kelas 3B'],

            // ===== Rabu =====
            ['employee_code' => 'EMP0001', 'tanggal' => $tgl(2), 'check_in' => $tgl(2).' 07:58:00', 'check_out' => $tgl(2).' 18:00:00', 'status' => 'tepat_waktu', 'late_minutes' => 0],
            ['employee_code' => 'EMP0014', 'tanggal' => $tgl(2), 'check_in' => $tgl(2).' 09:05:00', 'check_out' => $tgl(2).' 18:00:00', 'status' => 'tepat_waktu', 'late_minutes' => 0],
            ['employee_code' => 'EMP0016', 'tanggal' => $tgl(2), 'check_in' => $tgl(2).' 09:00:00', 'check_out' => $tgl(2).' 18:00:00', 'status' => 'tepat_waktu', 'late_minutes' => 0],

            // ===== Kamis =====
            ['employee_code' => 'EMP0002', 'tanggal' => $tgl(3), 'check_in' => $tgl(3).' 09:02:00', 'check_out' => $tgl(3).' 18:00:00', 'status' => 'tepat_waktu', 'late_minutes' => 0],
            ['employee_code' => 'EMP0003', 'tanggal' => $tgl(3), 'check_in' => $tgl(3).' 08:59:00', 'check_out' => $tgl(3).' 18:00:00', 'status' => 'tepat_waktu', 'late_minutes' => 0],
            ['employee_code' => 'EMP0011', 'tanggal' => $tgl(3), 'check_in' => $tgl(3).' 12:00:00', 'check_out' => $tgl(3).' 21:00:00', 'status' => 'tepat_waktu', 'late_minutes' => 0, 'activity' => 'Mengajar Kelas 6A'],

            // ===== Jumat =====
            ['employee_code' => 'EMP0001', 'tanggal' => $tgl(4), 'check_in' => $tgl(4).' 09:00:00', 'check_out' => $tgl(4).' 18:00:00', 'status' => 'tepat_waktu', 'late_minutes' => 0],
            ['employee_code' => 'EMP0012', 'tanggal' => $tgl(4), 'check_in' => $tgl(4).' 09:12:00', 'check_out' => $tgl(4).' 18:00:00', 'status' => 'terlambat', 'late_minutes' => 12],
            ['employee_code' => 'EMP0010', 'tanggal' => $tgl(4), 'check_in' => $tgl(4).' 12:00:00', 'check_out' => $tgl(4).' 21:00:00', 'status' => 'tepat_waktu', 'late_minutes' => 0],

            // ===== Sabtu (jam kerja lebih pendek) =====
            ['employee_code' => 'EMP0001', 'tanggal' => $tgl(5), 'check_in' => $tgl(5).' 08:55:00', 'check_out' => $tgl(5).' 16:00:00', 'status' => 'tepat_waktu', 'late_minutes' => 0],
            ['employee_code' => 'EMP0010', 'tanggal' => $tgl(5), 'check_in' => $tgl(5).' 12:05:00', 'check_out' => $tgl(5).' 18:00:00', 'status' => 'terlambat', 'late_minutes' => 5],
            ['employee_code' => 'EMP0013', 'tanggal' => $tgl(5), 'check_in' => $tgl(5).' 09:00:00', 'check_out' => $tgl(5).' 16:00:00', 'status' => 'tepat_waktu', 'late_minutes' => 0],
        ];

        foreach ($attendances as $row) {
            $employee = Employee::where('employee_code', $row['employee_code'])->firstOrFail();

            $tanggal = Carbon::parse($row['tanggal']);
            $hari = Shift::keyHari($tanggal);
            $shift = Shift::determineForCheckIn(Carbon::parse($row['check_in']), $hari);

            Attendance::create([
                'employee_id' => $employee->id,
                'branch_id' => $branch->id,
                'shift_id' => $shift?->id,
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