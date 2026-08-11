<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\ThrRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThrRecord>
 */
class ThrRecordFactory extends Factory
{
    protected $model = ThrRecord::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'year' => now()->year,
            'eligible' => true,
            'amount' => fake()->randomElement([3500000, 4000000, 4500000]),
            'note' => null,
        ];
    }
}
