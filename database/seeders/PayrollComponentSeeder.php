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
            'EMP0001' => [
                'base_salary' => 5000000, 'meal_rate' => 20000, 'transport_rate' => 15000,
                'hourly_rate' => 0, 'allowance' => 200000, 'thr_active' => true,
            ],
            'EMP0002' => [
                'base_salary' => 0, 'meal_rate' => 0, 'transport_rate' => 0,
                'hourly_rate' => 30000, 'allowance' => 0, 'thr_active' => false,
            ],
            // Tambahkan employee_code karyawan lain di sini...
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
