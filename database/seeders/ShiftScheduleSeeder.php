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
     * PRASYARAT: EmployeeSeeder & ShiftSeeder harus sudah dijalankan.
     */
    public function run(): void
    {
        // TODO: isi penugasan shift per karyawan yang sebenarnya.
        // Contoh format:
        // 'EMP0000' => ['shift' => Shift::where('name', 'Shift Pagi')->firstOrFail(), 'hari' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat']],
        $shiftScheduleData = [];

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