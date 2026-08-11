<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'name' => 'Shift Pagi',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'tolerance_minutes' => 10,
        ];
    }

    public function siang(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Siang',
            'start_time' => '13:00:00',
            'end_time' => '21:00:00',
        ]);
    }
}
