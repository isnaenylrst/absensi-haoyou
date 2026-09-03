<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\PayrollComponent;
use Illuminate\Database\Seeder;

class PayrollComponentSeeder extends Seeder
{
    /**
     * PRASYARAT: EmployeeSeeder harus sudah dijalankan.
     */
    public function run(): void
    {
        // TODO: sesuaikan nominal gaji dengan data asli per karyawan.
        $payroll = [
            // 'EMP0000' => [
            //     'base_salary' => 0, 'meal_rate' => 0, 'transport_rate' => 0,
            //     'hourly_rate' => 0, 'allowance' => 0, 'thr_active' => false,
            // ],
        ];

        foreach ($payroll as $employeeCode => $data) {
            $employee = Employee::where('employee_code', $employeeCode)->firstOrFail();

            PayrollComponent::updateOrCreate(
                ['employee_id' => $employee->id],
                array_merge($data, ['effective_date' => now()->format('Y-m-d')])
            );
        }
    }
}