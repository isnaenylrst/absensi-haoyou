<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Payslip;
use Illuminate\Database\Seeder;

class PayslipSeeder extends Seeder
{
    /**
     * PRASYARAT: EmployeeSeeder harus sudah dijalankan.
     */
    public function run(): void
    {
        // TODO: isi dari hasil rekap presensi & perhitungan gaji asli.
        $payslips = [
            [
                'employee_code' => 'EMP0001',
                'period_month' => 8,
                'period_year' => 2026,
                'hari_hadir' => 22,
                'total_pendapatan' => 5200000,
                'total_potongan' => 0,
                'total_diterima' => 5200000,
            ],
            // Tambahkan slip gaji karyawan lain di sini...
        ];

        foreach ($payslips as $row) {
            $employee = Employee::where('employee_code', $row['employee_code'])->firstOrFail();

            Payslip::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'period_month' => $row['period_month'],
                    'period_year' => $row['period_year'],
                ],
                [
                    'hari_hadir' => $row['hari_hadir'],
                    'total_pendapatan' => $row['total_pendapatan'],
                    'total_potongan' => $row['total_potongan'],
                    'total_diterima' => $row['total_diterima'],
                    'published_at' => now(),
                ]
            );
        }
    }
}
