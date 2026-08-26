<?php

namespace App\Services;

use App\Exceptions\AttendanceException;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\PartTimeSchedule;
use App\Models\Shift;
use App\Support\Geo;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttendanceService
{
    private const DAY_ORDER = [
        'senin' => 1, 'selasa' => 2, 'rabu' => 3, 'kamis' => 4,
        'jumat' => 5, 'sabtu' => 6, 'minggu' => 7,
    ];

    /**
     * Presensi masuk karyawan TETAP: shift dipilih manual, wajib foto dari kamera + koordinat.
     */
    public function checkInTetap(
        Employee $employee,
        int $shiftId,
        UploadedFile $photo,
        float $lat,
        float $lng,
        ?string $activity = null
    ): Attendance {
        $now = Carbon::now();
        $tanggal = $now->toDateString();

        $sudahCheckIn = Attendance::where('employee_id', $employee->id)
            ->where('tanggal', $tanggal)
            ->exists();

        if ($sudahCheckIn) {
            throw new AttendanceException('Anda sudah melakukan presensi masuk hari ini.');
        }

        $branch = $employee->branch;

        if (! $branch) {
            throw new AttendanceException('Karyawan belum terhubung dengan cabang manapun.');
        }

        $shift = Shift::find($shiftId);

        if (! $shift) {
            throw new AttendanceException('Shift yang dipilih tidak valid.');
        }

        // Cegah shift hari lain dipaksa lewat request manual (mis. hidden input diubah).
        $todayKey = array_flip(self::DAY_ORDER)[$now->dayOfWeekIso];

        if (! in_array($todayKey, $shift->applicable_days ?? [], true)) {
            throw new AttendanceException('Shift yang dipilih tidak berlaku untuk hari ini.');
        }

        $setting = AttendanceSetting::current();

        $distanceM = Geo::distanceMeters(
            (float) $branch->latitude,
            (float) $branch->longitude,
            $lat,
            $lng
        );

        if ($distanceM > $branch->radius_meter
            && $setting->out_of_radius_policy === 'ditolak_otomatis'
        ) {
            throw new AttendanceException(
                "Lokasi Anda berada {$distanceM} meter dari kantor (radius maksimal {$branch->radius_meter} meter). Presensi ditolak."
            );
        }

        $result = $shift->determineStatus($now);

        return DB::transaction(function () use (
            $employee, $branch, $shift, $now, $tanggal, $photo, $lat, $lng, $distanceM, $result, $activity
        ) {
            return Attendance::create([
                'employee_id' => $employee->id,
                'branch_id' => $branch->id,
                'shift_id' => $shift->id,
                'part_time_schedule_id' => null,
                'activity' => $activity,
                'tanggal' => $tanggal,
                'check_in' => $now,
                'check_in_lat' => $lat,
                'check_in_lng' => $lng,
                'distance_m' => $distanceM,
                'check_in_photo_url' => $this->storePhoto($photo, 'checkin'),
                'status' => $result['status'],
                'late_minutes' => $result['late_minutes'],
            ]);
        });
    }

    /**
     * Presensi pulang karyawan TETAP.
     */
    public function checkOutTetap(Employee $employee, UploadedFile $photo): Attendance
    {
        $tanggal = Carbon::now()->toDateString();

        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('tanggal', $tanggal)
            ->whereNull('check_out')
            ->first();

        if (! $attendance) {
            throw new AttendanceException('Anda belum melakukan check-in hari ini, atau sudah check-out.');
        }

        $attendance->update([
            'check_out' => Carbon::now(),
            'check_out_photo_url' => $this->storePhoto($photo, 'checkout'),
        ]);

        return $attendance;
    }

    /**
    * Presensi karyawan PART TIME: tanggal, jam mulai/selesai, dan keterangan diisi manual.
     */
    public function submitSesiPartTimeBatch(Employee $employee, array $sessions, string $tanggal): int
    {
        return DB::transaction(function () use ($employee, $sessions, $tanggal) {
            $ranges = [];

            foreach ($sessions as $session) {
                $checkIn = Carbon::parse("{$tanggal} {$session['start_time']}");
                $checkOut = Carbon::parse("{$tanggal} {$session['end_time']}");

                foreach ($ranges as [$existingCheckIn, $existingCheckOut]) {
                    if ($checkIn->lessThan($existingCheckOut) && $checkOut->greaterThan($existingCheckIn)) {
                        throw new AttendanceException('Sesi yang dikirim tumpang tindih satu sama lain.');
                    }
                }

                $overlap = PartTimeSchedule::where('employee_id', $employee->id)
                    ->where('tanggal', $tanggal)
                    ->where('start_time', '<', $session['end_time'])
                    ->where('end_time', '>', $session['start_time'])
                    ->exists();

                if ($overlap) {
                    throw new AttendanceException('Salah satu sesi tumpang tindih dengan sesi yang sudah Anda kirim hari ini.');
                }

                $ranges[] = [$checkIn, $checkOut];
            }

            foreach ($sessions as $session) {
                PartTimeSchedule::create([
                    'employee_id' => $employee->id,
                    'tanggal' => $tanggal,
                    'start_time' => $session['start_time'],
                    'end_time' => $session['end_time'],
                    'activity' => $session['activity'],
                    'hourly_rate' => 0,
                ]);
            }

            return count($sessions);
        });
    }

    private function storePhoto(UploadedFile $photo, string $prefix): string
    {
        $filename = now()->format('Y-m-d_His').'_'.str()->random(6).'.'.$photo->extension();

        Storage::disk('public')->putFileAs('attendance-photos', $photo, $filename);

        return "attendance-photos/{$filename}";
    }
}