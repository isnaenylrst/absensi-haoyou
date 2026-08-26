<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $weekday = ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
        $weekend = ['sabtu'];

        Shift::updateOrCreate(['name' => 'Shift Pagi'],
            ['start_time' => '09:00:00', 'end_time' => '18:00:00', 'tolerance_minutes' => 15, 'applicable_days' => $weekday]);

        Shift::updateOrCreate(['name' => 'Shift Siang'],
            ['start_time' => '12:00:00', 'end_time' => '21:00:00', 'tolerance_minutes' => 15, 'applicable_days' => $weekday]);

        Shift::updateOrCreate(['name' => 'Shift Pagi Sabtu'],
            ['start_time' => '09:00:00', 'end_time' => '16:00:00', 'tolerance_minutes' => 15, 'applicable_days' => $weekend]);

        Shift::updateOrCreate(['name' => 'Shift Siang Sabtu'],
            ['start_time' => '12:00:00', 'end_time' => '18:00:00', 'tolerance_minutes' => 15, 'applicable_days' => $weekend]);
    }
}