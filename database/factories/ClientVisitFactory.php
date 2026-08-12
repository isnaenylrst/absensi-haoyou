<?php

namespace Database\Factories;

use App\Models\ClientVisit;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientVisit>
 */
class ClientVisitFactory extends Factory
{
    protected $model = ClientVisit::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'client_name' => fake()->company(),
            'address' => fake()->address(),
            'visit_type' => fake()->randomElement(['Les Privat', 'Sales', 'Survei']),
            'latitude' => fake()->latitude(-8.5, -6.0),
            'longitude' => fake()->longitude(106.5, 113.5),
            'accuracy_m' => fake()->randomFloat(2, 5, 50),
            'photo_url' => 'https://picsum.photos/seed/' . fake()->uuid() . '/200',
            'notes' => fake()->sentence(),
            'visited_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'review_status' => fake()->randomElement(['wajar', 'wajar', 'perlu_ditinjau']),
        ];
    }
}
