<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Payslip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payslip>
 */
class PayslipFactory extends Factory
{
    protected $model = Payslip::class;

    public function definition(): array
    {
        $pendapatan = fake()->randomElement([3500000, 4000000, 4500000]);
        $potongan = fake()->numberBetween(0, 150000);

        return [
            'employee_id' => Employee::factory(),
            'period_month' => now()->month,
            'period_year' => now()->year,
            'hari_hadir' => fake()->numberBetween(18, 24),
            'total_pendapatan' => $pendapatan,
            'total_potongan' => $potongan,
            'total_diterima' => $pendapatan - $potongan,
            'published_at' => now(),
        ];
    }
}
