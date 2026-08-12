<?php

namespace Database\Seeders;

use App\Models\ClientVisit;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class ClientVisitSeeder extends Seeder
{
    /**
     * PRASYARAT: EmployeeSeeder harus sudah dijalankan.
     */
    public function run(): void
    {
        // TODO: isi dari data kunjungan klien sesungguhnya.
        $visits = [
            [
                'employee_code' => 'EMP0002',
                'client_name' => 'Nama Orang Tua Murid',
                'address' => 'Alamat lengkap lokasi kunjungan',
                'visit_type' => 'Les Privat',
                'photo_url' => 'https://contoh-storage.com/kunjungan/emp0002-2026-08-09.jpg',
                'notes' => 'Les privat Mandarin di rumah klien',
                'visited_at' => '2026-08-09 18:00:00',
            ],
            // Tambahkan kunjungan lain di sini...
        ];

        foreach ($visits as $row) {
            $employee = Employee::where('employee_code', $row['employee_code'])->firstOrFail();

            ClientVisit::create([
                'employee_id' => $employee->id,
                'client_name' => $row['client_name'],
                'address' => $row['address'],
                'visit_type' => $row['visit_type'],
                'photo_url' => $row['photo_url'],
                'notes' => $row['notes'],
                'visited_at' => $row['visited_at'],
                'review_status' => 'wajar',
            ]);
        }
    }
}