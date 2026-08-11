<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PayrollComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollComponent>
 */
class PayrollComponentFactory extends Factory
{
    protected $model = PayrollComponent::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'base_salary' => fake()->randomElement([3500000, 4000000, 4500000, 5000000]),
            'meal_rate' => 20000,
            'transport_rate' => 15000,
            'hourly_rate' => 0,
            'allowance' => fake()->randomElement([0, 100000, 200000]),
            'thr_active' => true,
            'effective_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
