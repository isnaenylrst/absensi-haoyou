<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\PartTimeSchedule;
use Illuminate\Database\Seeder;
use RuntimeException;

class PartTimeScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Hapus dulu semua jadwal part-time milik karyawan yang statusnya
        // BUKAN part_time (jaga-jaga kalau employee_type-nya berubah).
        PartTimeSchedule::whereHas('employee', function ($query) {
            $query->where('employee_type', '!=', 'part_time');
        })->delete();

        foreach ($this->scheduleData() as $employeeCode => $sessions) {
            $employee = Employee::where('employee_code', $employeeCode)->firstOrFail();

            // Pengaman: tolak kalau employee_type bukan 'part_time'
            if ($employee->employee_type !== 'part_time') {
                throw new RuntimeException(
                    "PartTimeScheduleSeeder: {$employeeCode} ({$employee->full_name}) ".
                    "employee_type-nya '{$employee->employee_type}', bukan 'part_time'. ".
                    "Jadwal per jam hanya untuk karyawan part-time - cek EmployeeSeeder atau hapus baris ini."
                );
            }

            // Kumpulkan kombinasi (hari, start_time) yang valid untuk employee ini,
            // supaya sesi lama yang sudah tidak ada di data terbaru ikut terhapus.
            $validKeys = [];

            foreach ($sessions as $session) {
                $this->assertNoOverlap($employeeCode, $sessions, $session);

                PartTimeSchedule::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'day_of_week' => $session['day'],
                        'start_time' => $session['start'],
                    ],
                    [
                        'end_time' => $session['end'],
                        'activity' => $session['activity'],
                        'hourly_rate' => $session['rate'],
                    ]
                );

                $validKeys[] = $session['day'].'|'.$session['start'];
            }

            // Bersihkan sesi lama employee ini yang sudah tidak ada di scheduleData()
            $employee->partTimeSchedules()
                ->get()
                ->reject(fn ($row) => in_array($row->day_of_week.'|'.substr($row->start_time, 0, 5), $validKeys))
                ->each->delete();
        }
    }
    
    private function assertNoOverlap(string $employeeCode, array $sessions, array $current): void
    {
        foreach ($sessions as $other) {
            if ($other === $current || $other['day'] !== $current['day']) {
                continue;
            }

            $overlap = $current['start'] < $other['end'] && $other['start'] < $current['end'];

            if ($overlap) {
                throw new RuntimeException(
                    "PartTimeScheduleSeeder: {$employeeCode} punya jadwal tumpang tindih di hari ".
                    "{$current['day']}: {$current['start']}-{$current['end']} vs {$other['start']}-{$other['end']}."
                );
            }
        }
    }

    private function scheduleData(): array
    {
        return [
            'EMP0004' => [ // Teresa Liaunardo Tju
                ['day' => 'senin', 'start' => '15:00', 'end' => '17:00', 'activity' => 'Mengajar Kelas 5A', 'rate' => 30000],
                ['day' => 'rabu', 'start' => '09:00', 'end' => '10:00', 'activity' => 'Mengajar Kelas 5A', 'rate' => 30000], // TODO: contoh sesi pagi
                ['day' => 'rabu', 'start' => '13:00', 'end' => '14:30', 'activity' => 'Les Privat', 'rate' => 30000],        // TODO: contoh sesi siang
                ['day' => 'rabu', 'start' => '17:00', 'end' => '18:00', 'activity' => 'Mengajar Kelas 5A', 'rate' => 30000], // TODO: contoh sesi sore
            ],
            'EMP0005' => [ // Vanessa Tan
                ['day' => 'selasa', 'start' => '13:00', 'end' => '15:00', 'activity' => 'Mengajar Kelas 3B', 'rate' => 30000],
                ['day' => 'kamis', 'start' => '13:00', 'end' => '15:00', 'activity' => 'Mengajar Kelas 3B', 'rate' => 30000],
            ],
            'EMP0006' => [ // Natalia Repiuli Dertina Sitorus
                ['day' => 'senin', 'start' => '17:00', 'end' => '19:00', 'activity' => 'Les Privat Mandarin', 'rate' => 35000],
            ],
            'EMP0007' => [ // Kezia Sophi Adventa Ratta
                ['day' => 'jumat', 'start' => '14:00', 'end' => '16:00', 'activity' => 'Mengajar Kelas 6A', 'rate' => 30000],
            ],
            'EMP0008' => [ // Marsya Amelia
                ['day' => 'sabtu', 'start' => '09:00', 'end' => '11:00', 'activity' => 'Les Privat Mandarin', 'rate' => 35000],
            ],
        ];
    }
}