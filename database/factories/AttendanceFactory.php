<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $tanggal = fake()->dateTimeBetween('-30 days', 'now');
        $status = fake()->randomElement(['tepat_waktu', 'tepat_waktu', 'tepat_waktu', 'terlambat', 'alpa']);
        $lateMinutes = $status === 'terlambat' ? fake()->numberBetween(1, 45) : 0;

        return [
            'employee_id' => Employee::factory(),
            'shift_schedule_id' => null,
            'part_time_schedule_id' => null,
            'branch_id' => Branch::factory(),
            'tanggal' => $tanggal->format('Y-m-d'),
            'check_in' => $status !== 'alpa' ? $tanggal : null,
            'check_out' => $status !== 'alpa' ? (clone $tanggal)->modify('+8 hours') : null,
            'check_in_lat' => fake()->latitude(-8.5, -6.0),
            'check_in_lng' => fake()->longitude(106.5, 113.5),
            'distance_m' => fake()->randomFloat(2, 0, 80),
            'photo_url' => $status !== 'alpa' ? 'https://picsum.photos/seed/' . fake()->uuid() . '/200' : null,
            'status' => $status,
            'late_minutes' => $lateMinutes,
        ];
    }

    /**
     * Set tanggal presensi secara eksplisit (dipakai saat seeding banyak
     * baris per karyawan, supaya tidak bentrok dengan UNIQUE(employee_id, tanggal)
     * dan supaya check_in/check_out tetap konsisten dengan tanggalnya).
     */
    public function forDate(\DateTimeInterface|string $date): static
    {
        $date = Carbon::parse($date);
        $status = fake()->randomElement(['tepat_waktu', 'tepat_waktu', 'tepat_waktu', 'terlambat', 'alpa']);
        $lateMinutes = $status === 'terlambat' ? fake()->numberBetween(1, 45) : 0;

        return $this->state(function (array $attributes) use ($date, $status, $lateMinutes) {
            return [
                'tanggal' => $date->format('Y-m-d'),
                'check_in' => $status !== 'alpa' ? $date->copy()->setTime(8, 0)->addMinutes($lateMinutes) : null,
                'check_out' => $status !== 'alpa' ? $date->copy()->setTime(16, 0) : null,
                'status' => $status,
                'late_minutes' => $lateMinutes,
            ];
        });
    }

    /**
     * Untuk karyawan part-time: kaitkan presensi ke satu part_time_schedule
     * spesifik pada tanggal tertentu, dengan jam check-in/out mengikuti
     * jam jadwal tersebut.
     */
    public function forPartTimeSession(\App\Models\PartTimeSchedule $schedule, \DateTimeInterface|string $date, int $branchId): static
    {
        $date = Carbon::parse($date);
        $status = fake()->randomElement(['tepat_waktu', 'tepat_waktu', 'tepat_waktu', 'terlambat', 'alpa']);
        $lateMinutes = $status === 'terlambat' ? fake()->numberBetween(1, 20) : 0;

        $checkIn = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time)->addMinutes($lateMinutes);
        $checkOut = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);

        return $this->state(function (array $attributes) use ($schedule, $date, $status, $lateMinutes, $checkIn, $checkOut, $branchId) {
            return [
                'employee_id' => $schedule->employee_id,
                'part_time_schedule_id' => $schedule->id,
                'activity' => $schedule->activity,
                'shift_schedule_id' => null,
                'branch_id' => $branchId,
                'tanggal' => $date->format('Y-m-d'),
                'check_in' => $status !== 'alpa' ? $checkIn : null,
                'check_out' => $status !== 'alpa' ? $checkOut : null,
                'status' => $status,
                'late_minutes' => $lateMinutes,
            ];
        });
    }
}
