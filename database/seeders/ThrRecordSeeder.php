<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\ThrRecord;
use Illuminate\Database\Seeder;

class ThrRecordSeeder extends Seeder
{
    /**
     * PRASYARAT: EmployeeSeeder harus sudah dijalankan.
     */
    public function run(): void
    {
        // TODO: sesuaikan dengan masa kerja & kebijakan THR asli.
        $thr = [
            'EMP0001' => ['year' => 2026, 'eligible' => true, 'amount' => 5000000],
            // Tambahkan employee_code karyawan lain di sini...
        ];

        foreach ($thr as $employeeCode => $data) {
            $employee = Employee::where('employee_code', $employeeCode)->firstOrFail();

            ThrRecord::updateOrCreate(
                ['employee_id' => $employee->id, 'year' => $data['year']],
                ['eligible' => $data['eligible'], 'amount' => $data['amount']]
            );
        }
    }
}
