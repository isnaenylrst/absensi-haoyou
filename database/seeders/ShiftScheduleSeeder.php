<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use Illuminate\Database\Seeder;
use RuntimeException;

class ShiftScheduleSeeder extends Seeder
{
    /**
     * PRASYARAT: ShiftSeeder & EmployeeSeeder harus sudah dijalankan.
     *
     * PENGAMAN: seeder ini akan GAGAL (throw exception) kalau employee_code
     * yang didaftarkan di sini ternyata employee_type-nya bukan 'tetap' -
     * supaya tidak kejadian lagi shift ke-assign ke karyawan yang salah
     * (seperti kasus EMP0002 sebelumnya).
     */
    public function run(): void
    {
        $shiftPagi = Shift::where('name', 'Shift Pagi')->firstOrFail();
        $shiftSiang = Shift::where('name', 'Shift Siang')->firstOrFail();

        $hariKerja = ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];

        // ============================================================
        // Semua karyawan tetap (employee_type = 'tetap') di data asli.
        // TODO: kalau ada yang jamnya beda (mis. Cleaning Service masuk
        // siang), ganti $shiftPagi jadi $shiftSiang untuk employee_code
        // tersebut, atau sesuaikan $hariKerja-nya kalau ada yang shift-nya
        // tidak Senin-Jumat penuh.
        // ============================================================
        $shiftScheduleData = [
            'EMP0001' => ['shift' => $shiftPagi, 'hari' => $hariKerja], // Owner
            'EMP0002' => ['shift' => $shiftPagi, 'hari' => $hariKerja], // Admin
            'EMP0003' => ['shift' => $shiftPagi, 'hari' => $hariKerja], // Admin
            'EMP0009' => ['shift' => $shiftSiang, 'hari' => $hariKerja], // Cleaning Service
            'EMP0010' => ['shift' => $shiftPagi, 'hari' => $hariKerja], // Teacher (tetap)
            'EMP0011' => ['shift' => $shiftPagi, 'hari' => $hariKerja], // Course Consultant
            'EMP0012' => ['shift' => $shiftPagi, 'hari' => $hariKerja], // Course Consultant
            'EMP0013' => ['shift' => $shiftPagi, 'hari' => $hariKerja], // Course Consultant
            'EMP0014' => ['shift' => $shiftPagi, 'hari' => $hariKerja], // Teacher (tetap)
            'EMP0017' => ['shift' => $shiftPagi, 'hari' => $hariKerja], // Course Consultant
        ];

        foreach ($shiftScheduleData as $employeeCode => $data) {
            $employee = Employee::where('employee_code', $employeeCode)->firstOrFail();

            // Pengaman: tolak kalau employee_type bukan 'tetap'
            if ($employee->employee_type !== 'tetap') {
                throw new RuntimeException(
                    "ShiftScheduleSeeder: {$employeeCode} ({$employee->full_name}) ".
                    "employee_type-nya '{$employee->employee_type}', bukan 'tetap'. ".
                    "Shift hanya untuk karyawan tetap - cek EmployeeSeeder atau hapus baris ini."
                );
            }

            foreach ($data['hari'] as $hari) {
                ShiftSchedule::updateOrCreate(
                    ['employee_id' => $employee->id, 'day_of_week' => $hari],
                    ['shift_id' => $data['shift']->id]
                );
            }
        }
    }
} 