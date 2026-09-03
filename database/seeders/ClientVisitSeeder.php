<?php

namespace Database\Seeders;

use App\Models\ClientVisit;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class ClientVisitSeeder extends Seeder
{
    /**
     * PRASYARAT: EmployeeSeeder harus sudah dijalankan lebih dulu.
     */
    public function run(): void
    {
        // TODO: isi dari riwayat kunjungan klien asli.
        $visits = [
            // [
            //     'employee_code' => 'EMP0000',
            //     'client_name' => '',
            //     'address' => '',
            //     'visit_type' => '',
            //     'latitude' => null,
            //     'longitude' => null,
            //     'accuracy_m' => null,
            //     'photo_url' => null,
            //     'notes' => null,
            //     'visited_at' => '',
            //     'review_status' => 'wajar',
            // ],
        ];

        foreach ($visits as $row) {
            $employee = Employee::where('employee_code', $row['employee_code'])->firstOrFail();

            ClientVisit::create([
                'employee_id' => $employee->id,
                'client_name' => $row['client_name'],
                'address' => $row['address'],
                'visit_type' => $row['visit_type'],
                'latitude' => $row['latitude'] ?? null,
                'longitude' => $row['longitude'] ?? null,
                'accuracy_m' => $row['accuracy_m'] ?? null,
                'photo_url' => $row['photo_url'] ?? null,
                'notes' => $row['notes'] ?? null,
                'visited_at' => $row['visited_at'],
                'review_status' => $row['review_status'] ?? 'wajar',
            ]);
        }
    }
}