<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PartTimeSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartTimeSchedule>
 */
class PartTimeScheduleFactory extends Factory
{
    protected $model = PartTimeSchedule::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'tanggal' => fake()->dateTimeBetween('-1 month', 'today')->format('Y-m-d'),
            'day_of_week' => null,
            'start_time' => '15:00:00',
            'end_time' => '17:00:00',
            'activity' => fake()->randomElement(['Mengajar Kelas 5A', 'Les Privat', 'Bimbingan Belajar']),
            'hourly_rate' => fake()->randomElement([25000, 30000, 35000]),
        ];
    }
}
