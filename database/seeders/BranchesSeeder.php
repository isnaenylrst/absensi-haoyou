<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchesSeeder extends Seeder
{
    public function run(): void
    {
       
     Branch::updateOrCreate(
            ['name' => 'Haoyou Educator'],
            [
                'address' => 'Jl. Terusan Dieng No.9E, Pisang Candi, Kec. Sukun, Kota Malang, Jawa Timur 65115',
                'latitude' => -7.972468,   // TODO: ganti dengan titik GPS asli
                'longitude' => 112.60689, // TODO: ganti dengan titik GPS asli
                'radius_meter' => 150,      // TODO: ganti = jarak ke titik terjauh + 2 meter
            ]
        );
    }
}