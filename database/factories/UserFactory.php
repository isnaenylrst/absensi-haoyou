<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'username' => fake()->unique()->userName(),
            'password_hash' => static::$password ??= Hash::make('password'),
            'role' => 'karyawan',
            'status_akun' => 'aktif',
            'last_login' => null,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'owner',
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_akun' => 'nonaktif',
        ]);
    }
}
