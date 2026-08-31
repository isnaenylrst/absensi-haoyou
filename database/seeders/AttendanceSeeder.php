<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $mulai = Carbon::create(2026, 8, 1)->startOfDay();
        $selesai = Carbon::create(2026, 8, 25)->endOfDay();

        $employees = Employee::query()
            ->where('employee_type', 'tetap')
            ->with(['branch', 'shiftSchedules.shift'])
            ->get();

        foreach ($employees as $employee) {
            for ($tanggal = $mulai->copy(); $tanggal->lte($selesai); $tanggal->addDay()) {
                if ($tanggal->isSunday() || $tanggal->lt($employee->join_date)) {
                    continue;
                }

                $dayNames = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
                $day = $dayNames[$tanggal->dayOfWeekIso - 1];
                $shiftSchedule = $employee->shiftSchedules->firstWhere('day_of_week', $day);

                // Beberapa hari kosong agar data terasa seperti absensi nyata.
                if (! $shiftSchedule || (($tanggal->day + $employee->id) % 7 === 0)) {
                    continue;
                }

                $shift = $shiftSchedule->shift;
                $isLate = (($tanggal->day + $employee->id) % 9 === 0);
                $lateMinutes = $isLate ? 16 + (($tanggal->day + $employee->id) % 18) : 0;
                $checkIn = Carbon::parse($tanggal->toDateString().' '.$shift->start_time)->addMinutes($lateMinutes);
                $checkOut = Carbon::parse($tanggal->toDateString().' '.$shift->end_time)
                    ->subMinutes((($tanggal->day + $employee->id) % 4) * 3);
                $outOfRadius = (($tanggal->day + $employee->id) % 11 === 0);

                Attendance::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'tanggal' => $tanggal->toDateString(),
                        'shift_id' => $shift->id,
                    ],
                    [
                        'branch_id' => $employee->branch_id,
                        'shift_schedule_id' => $shiftSchedule->id,
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'check_in_lat' => $outOfRadius ? -7.9510000 : -7.9501000,
                        'check_in_lng' => $outOfRadius ? 112.6100000 : 112.6101000,
                        'distance_m' => $outOfRadius ? 387 : 12 + (($tanggal->day + $employee->id) % 22),
                        'status' => $isLate ? 'terlambat' : 'tepat_waktu',
                        'late_minutes' => $lateMinutes,
                    ]
                );
            }
        }
    }
}