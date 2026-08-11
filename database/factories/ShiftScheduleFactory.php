<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftSchedule>
 */
class ShiftScheduleFactory extends Factory
{
    protected $model = ShiftSchedule::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'shift_id' => Shift::factory(),
            'day_of_week' => fake()->randomElement(['senin', 'selasa', 'rabu', 'kamis', 'jumat']),
        ];
    }
}
