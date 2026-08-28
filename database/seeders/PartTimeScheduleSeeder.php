<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\PartTimeSchedule;
use Illuminate\Database\Seeder;

class PartTimeScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // AMBIL SEMUA GURU
        // Patokan: position = Teacher / Guru
        // Tidak peduli employee_type tetap / part_time
        // ============================================================
        $employees = Employee::query()
            ->where(function ($query) {
                $query->whereRaw('LOWER(position) = ?', ['teacher'])
                    ->orWhereRaw('LOWER(position) = ?', ['guru']);
            })
            ->orderBy('id')
            ->get();

        if ($employees->isEmpty()) {
            $this->command->warn('Tidak ada employee dengan position Teacher/Guru.');
            return;
        }

        // ============================================================
        // DATA JADWAL MINGGUAN
        //
        // Setiap item = 1 sesi mengajar
        // ============================================================
        $jadwal = [
            'senin' => [
                ['13:00:00', '14:30:00'],
                ['15:00:00', '16:00:00'],
                ['17:30:00', '19:00:00'],
            ],

            'selasa' => [
                ['12:00:00', '13:30:00'],
                ['15:00:00', '16:00:00'],
            ],

            'rabu' => [
                ['09:00:00', '10:00:00'],
                ['14:00:00', '15:00:00'],
                ['17:00:00', '18:00:00'],
            ],

            'kamis' => [
                ['13:00:00', '14:30:00'],
                ['16:00:00', '17:30:00'],
            ],

            'jumat' => [
                ['09:00:00', '10:30:00'],
                ['15:00:00', '16:30:00'],
            ],

            'sabtu' => [
                ['09:00:00', '10:30:00'],
                ['13:00:00', '14:30:00'],
                ['16:00:00', '17:30:00'],
            ],

            // Minggu libur
            'minggu' => [],
        ];

        // ============================================================
        // BUAT JADWAL UNTUK SETIAP GURU
        // ============================================================
        foreach ($employees as $employee) {

            foreach ($jadwal as $hari => $sesiList) {

                foreach ($sesiList as $sesi) {

                    [$startTime, $endTime] = $sesi;

                    PartTimeSchedule::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'day_of_week' => $hari,
                            'start_time' => $startTime,
                        ],
                        [
                            'end_time' => $endTime,
                            'activity' => 'Mengajar Kelas',
                            'hourly_rate' => 30000,
                        ]
                    );
                }
            }
        }

        $this->command->info(
            'Jadwal part-time berhasil dibuat untuk ' .
            $employees->count() .
            ' guru.'
        );
    }
}