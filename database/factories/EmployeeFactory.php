<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'employee_code' => 'EMP' . fake()->unique()->numerify('####'),
            'full_name' => fake()->name(),
            'gender' => fake()->randomElement(['laki-laki', 'perempuan']),
            'nationality' => 'Indonesia',
            'religion' => fake()->randomElement(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']),
            'blood_type' => fake()->randomElement(['A', 'B', 'AB', 'O']),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-45 years', '-20 years'),
            'marital_status' => fake()->randomElement(['belum_menikah', 'menikah', 'cerai']),
            'last_education' => fake()->randomElement(['SMA', 'D3', 'S1', 'S2']),
            'phone' => fake()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'position' => fake()->randomElement(['Guru', 'Admin', 'Sales', 'Front Office', 'Staff Operasional']),
            'employee_type' => fake()->randomElement(['tetap', 'part_time']),
            'join_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'id_document_type' => 'KTP',
        ];
    }

    public function tetap(): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_type' => 'tetap',
        ]);
    }

    public function partTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_type' => 'part_time',
        ]);
    }
}
