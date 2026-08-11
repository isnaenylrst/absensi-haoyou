<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-20 days', '+10 days');
        $duration = fake()->numberBetween(1, 3);
        $status = fake()->randomElement(['menunggu', 'disetujui', 'ditolak']);

        return [
            'employee_id' => Employee::factory(),
            'approved_by' => null,
            'leave_type' => fake()->randomElement(['sakit', 'cuti_tahunan', 'izin_pribadi', 'dinas_luar']),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => (clone $start)->modify("+{$duration} days")->format('Y-m-d'),
            'duration_days' => $duration,
            'reason' => fake()->sentence(),
            'attachment_url' => fake()->boolean(40) ? 'https://picsum.photos/seed/' . fake()->uuid() . '/300' : null,
            'status' => $status,
            'approved_at' => $status !== 'menunggu' ? fake()->dateTimeBetween('-20 days', 'now') : null,
        ];
    }
}
