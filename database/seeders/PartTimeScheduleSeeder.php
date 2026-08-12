<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\PartTimeSchedule;
use Illuminate\Database\Seeder;
use RuntimeException;

class PartTimeScheduleSeeder extends Seeder
{
    /**
     * PRASYARAT: EmployeeSeeder harus sudah dijalankan.
     *
     * PENGAMAN: seeder ini akan GAGAL (throw exception) kalau employee_code
     * yang didaftarkan di sini ternyata employee_type-nya bukan 'part_time'.
     *
     * PEMBERSIHAN DATA LAMA: hapus dulu part_time_schedules yang salah
     * sasaran (kemarin ke-assign ke EMP0002 yang sebenarnya 'tetap'),
     * supaya tidak ada data nyangkut dari kesalahan sebelumnya.
     */
    public function run(): void
    {
        $employeeCodeYangBenar = array_keys($this->scheduleData());

        // Hapus part_time_schedules milik karyawan yang TIDAK ada di daftar
        // di bawah (mis. EMP0002 dari kesalahan sebelumnya) tapi HANYA
        // kalau karyawan tersebut memang bukan part_time - supaya aman.
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

            foreach ($sessions as $session) {
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
            }
        }
    }

    /**
     * TODO: ganti jadwal contoh di bawah dengan jadwal asli tiap guru.
     * 5 karyawan part-time (posisi "Teacher") di data asli:
     * EMP0004, EMP0005, EMP0006, EMP0007, EMP0008.
     */
    private function scheduleData(): array
    {
        return [
            'EMP0004' => [ // Teresa Liaunardo Tju
                ['day' => 'senin', 'start' => '15:00', 'end' => '17:00', 'activity' => 'Mengajar Kelas 5A', 'rate' => 30000],
                ['day' => 'rabu', 'start' => '15:00', 'end' => '17:00', 'activity' => 'Mengajar Kelas 5A', 'rate' => 30000],
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