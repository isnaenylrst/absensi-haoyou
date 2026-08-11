<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Branch;
use App\Models\ClientVisit;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PartTimeSchedule;
use App\Models\PayrollComponent;
use App\Models\Payslip;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\ThrRecord;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Pengaturan presensi (singleton, 1 baris untuk seluruh sistem)
        AttendanceSetting::current();

        // 2. Dua cabang kantor
        $branchSurabaya = Branch::factory()->create([
            'name' => 'Kantor Cabang Surabaya',
            'latitude' => -7.257472,
            'longitude' => 112.752090,
            'radius_meter' => 150,
        ]);

        $branchMalang = Branch::factory()->create([
            'name' => 'Kantor Cabang Malang',
            'latitude' => -7.966620,
            'longitude' => 112.632629,
            'radius_meter' => 150,
        ]);

        $branches = [$branchSurabaya, $branchMalang];

        // 3. Master shift
        $shiftPagi = Shift::factory()->create();
        $shiftSiang = Shift::factory()->siang()->create();

        // 4. Owner (akun pertama, karyawan tetap di cabang Surabaya)
        $ownerEmployee = Employee::factory()->tetap()->create([
            'branch_id' => $branchSurabaya->id,
            'employee_code' => 'EMP0001',
            'full_name' => 'Budi Santoso',
            'position' => 'Owner',
        ]);

        User::factory()->owner()->create([
            'employee_id' => $ownerEmployee->id,
            'username' => 'owner',
        ]);

        PayrollComponent::factory()->create([
            'employee_id' => $ownerEmployee->id,
        ]);

        // 5. Karyawan tetap: 4 orang, tersebar di 2 cabang, dapat shift & payroll
        foreach ($branches as $branch) {
            Employee::factory()
                ->tetap()
                ->count(2)
                ->create(['branch_id' => $branch->id])
                ->each(function (Employee $employee) use ($shiftPagi, $shiftSiang, $branch) {
                    User::factory()->create(['employee_id' => $employee->id]);

                    PayrollComponent::factory()->create(['employee_id' => $employee->id]);

                    foreach (['senin', 'selasa', 'rabu', 'kamis', 'jumat'] as $day) {
                        ShiftSchedule::factory()->create([
                            'employee_id' => $employee->id,
                            'shift_id' => fake()->randomElement([$shiftPagi->id, $shiftSiang->id]),
                            'day_of_week' => $day,
                        ]);
                    }

                    // Presensi 15 hari kerja terakhir - tanggal dibuat eksplisit
                    // (bukan acak) supaya tidak pernah tabrakan dengan UNIQUE
                    // (employee_id, tanggal, part_time_schedule_id).
                    collect(range(0, 14))->each(function (int $i) use ($employee, $branch) {
                        Attendance::factory()
                            ->forDate(now()->subDays($i))
                            ->create([
                                'employee_id' => $employee->id,
                                'branch_id' => $branch->id,
                            ]);
                    });

                    Payslip::factory()->create(['employee_id' => $employee->id]);
                    ThrRecord::factory()->create(['employee_id' => $employee->id]);
                });
        }

        // 6. Karyawan part-time: 3 orang, jadwal per jam + rate per jam
        foreach ($branches as $branch) {
            Employee::factory()
                ->partTime()
                ->count(fake()->numberBetween(1, 2))
                ->create(['branch_id' => $branch->id])
                ->each(function (Employee $employee) use ($branch) {
                    User::factory()->create(['employee_id' => $employee->id]);

                    PayrollComponent::factory()->create([
                        'employee_id' => $employee->id,
                        'base_salary' => 0,
                        'hourly_rate' => fake()->randomElement([25000, 30000, 35000]),
                        'thr_active' => false,
                    ]);

                    $schedules = PartTimeSchedule::factory()
                        ->count(3)
                        ->create(['employee_id' => $employee->id]);

                    // Presensi 5 hari terakhir, untuk SETIAP jadwal yang dimiliki
                    // karyawan ini -> bisa lebih dari 1 presensi per hari selama
                    // schedule-nya berbeda (mis. sesi pagi & sore hari yang sama).
                    collect(range(0, 4))->each(function (int $i) use ($schedules, $branch) {
                        $schedules->each(function (PartTimeSchedule $schedule) use ($i, $branch) {
                            Attendance::factory()
                                ->forPartTimeSession($schedule, now()->subDays($i), $branch->id)
                                ->create();
                        });
                    });

                    ClientVisit::factory()
                        ->count(2)
                        ->create(['employee_id' => $employee->id]);
                });
        }

        // 7. Beberapa pengajuan izin/cuti untuk sample data approval
        Employee::inRandomOrder()->take(5)->get()->each(function (Employee $employee) {
            LeaveRequest::factory()->create(['employee_id' => $employee->id]);
        });
    }
}
