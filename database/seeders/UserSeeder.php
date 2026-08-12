<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    
    public function run(): void
    {
        $users = [
            ['employee_code' => 'EMP0001', 'username' => 'owner', 'role' => 'owner'],
            ['employee_code' => 'EMP0002', 'username' => 'rina', 'role' => 'karyawan'],
            ['employee_code' => 'EMP0003', 'username' => 'uziel', 'role' => 'karyawan'],
            ['employee_code' => 'EMP0004', 'username' => 'teresa', 'role' => 'karyawan'],
            ['employee_code' => 'EMP0005', 'username' => 'vanessa', 'role' => 'karyawan'],
            ['employee_code' => 'EMP0006', 'username' => 'natalia', 'role' => 'karyawan'],
            ['employee_code' => 'EMP0007', 'username' => 'kezia', 'role' => 'karyawan'],
            ['employee_code' => 'EMP0008', 'username' => 'marsya', 'role' => 'karyawan'],
            ['employee_code' => 'EMP0009', 'username' => 'nathania', 'role' => 'karyawan'],
            ['employee_code' => 'EMP0010', 'username' => 'achmad', 'role' => 'karyawan'],
            ['employee_code' => 'EMP0011', 'username' => 'fitri', 'role' => 'karyawan'],
            ['employee_code' => 'EMP0012', 'username' => 'deva', 'role' => 'karyawan'],
            ['employee_code' => 'EMP0013', 'username' => 'eka', 'role' => 'karyawan'],
            ['employee_code' => 'EMP0014', 'username' => 'isnaeny', 'role' => 'karyawan'],
            ['employee_code' => 'EMP0015', 'username' => 'dwiayu', 'role' => 'karyawan'],
            ['employee_code' => 'EMP0016', 'username' => 'febry', 'role' => 'karyawan'],
        ];
            
        foreach ($users as $data) {
            $employee = Employee::where('employee_code', $data['employee_code'])->firstOrFail();

            User::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'username' => $data['username'],
                    'password_hash' => Hash::make('password'), // TODO: ganti sebelum produksi
                    'role' => $data['role'],
                    'status_akun' => 'aktif',
                ]
            );
        }
    }
}