<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'name' => 'Kantor Cabang ' . fake()->city(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(-8.5, -6.0),
            'longitude' => fake()->longitude(106.5, 113.5),
            'radius_meter' => fake()->randomElement([100, 150, 200]),
        ];
    }
}
