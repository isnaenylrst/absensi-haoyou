<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::where('name', 'Haoyou Educator')->firstOrFail();

        $dummyCheckInPhoto = $this->ensureDummyPhoto('seed-photos/dummy-checkin.jpg', 'DUMMY PHOTO - CHECK IN');
        $dummyCheckOutPhoto = $this->ensureDummyPhoto('seed-photos/dummy-checkout.jpg', 'DUMMY PHOTO - CHECK OUT');

        $senin = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $tgl = fn (int $offsetHari) => $senin->copy()->addDays($offsetHari)->format('Y-m-d');

        $branchLatitude = (float) $branch->latitude;
        $branchLongitude = (float) $branch->longitude;

        $attendances = [
            // ===== Senin =====
            ['employee_code' => 'EMP0001', 'tanggal' => $tgl(0), 'check_in' => $tgl(0).' 07:55:00', 'check_out' => $tgl(0).' 18:05:00', 'latitude' => -7.972500, 'longitude' => 112.606900],
            ['employee_code' => 'EMP0011', 'tanggal' => $tgl(0), 'check_in' => $tgl(0).' 09:10:00', 'check_out' => $tgl(0).' 18:00:00', 'latitude' => -7.972700, 'longitude' => 112.607000],
            ['employee_code' => 'EMP0012', 'tanggal' => $tgl(0), 'check_in' => $tgl(0).' 12:05:00', 'check_out' => $tgl(0).' 21:00:00', 'latitude' => -7.974500, 'longitude' => 112.609000],
            ['employee_code' => 'EMP0010', 'tanggal' => $tgl(0), 'check_in' => $tgl(0).' 12:20:00', 'check_out' => $tgl(0).' 21:00:00', 'latitude' => -7.975000, 'longitude' => 112.610000],

            // ===== Selasa =====
            ['employee_code' => 'EMP0001', 'tanggal' => $tgl(1), 'check_in' => $tgl(1).' 08:50:00', 'check_out' => $tgl(1).' 18:00:00', 'latitude' => -7.972400, 'longitude' => 112.606800],
            ['employee_code' => 'EMP0013', 'tanggal' => $tgl(1), 'check_in' => $tgl(1).' 09:20:00', 'check_out' => $tgl(1).' 18:00:00', 'latitude' => -7.972600, 'longitude' => 112.606950],
            ['employee_code' => 'EMP0015', 'tanggal' => $tgl(1), 'check_in' => $tgl(1).' 12:15:00', 'check_out' => $tgl(1).' 21:00:00', 'activity' => 'Mengajar Kelas 3B', 'latitude' => -7.974800, 'longitude' => 112.609500],

            // ===== Rabu =====
            ['employee_code' => 'EMP0001', 'tanggal' => $tgl(2), 'check_in' => $tgl(2).' 07:58:00', 'check_out' => $tgl(2).' 18:00:00', 'latitude' => -7.972550, 'longitude' => 112.606850],
            ['employee_code' => 'EMP0014', 'tanggal' => $tgl(2), 'check_in' => $tgl(2).' 09:25:00', 'check_out' => $tgl(2).' 18:00:00', 'latitude' => -7.972800, 'longitude' => 112.606700],
            ['employee_code' => 'EMP0016', 'tanggal' => $tgl(2), 'check_in' => $tgl(2).' 09:00:00', 'check_out' => $tgl(2).' 18:00:00', 'latitude' => -7.975500, 'longitude' => 112.610500],

            // ===== Kamis =====
            ['employee_code' => 'EMP0002', 'tanggal' => $tgl(3), 'check_in' => $tgl(3).' 09:20:00', 'check_out' => $tgl(3).' 18:00:00', 'latitude' => -7.972300, 'longitude' => 112.606900],
            ['employee_code' => 'EMP0003', 'tanggal' => $tgl(3), 'check_in' => $tgl(3).' 08:59:00', 'check_out' => $tgl(3).' 18:00:00', 'latitude' => -7.974200, 'longitude' => 112.608500],
            ['employee_code' => 'EMP0011', 'tanggal' => $tgl(3), 'check_in' => $tgl(3).' 12:00:00', 'check_out' => $tgl(3).' 21:00:00', 'activity' => 'Mengajar Kelas 6A', 'latitude' => -7.972650, 'longitude' => 112.606900],

            // ===== Jumat =====
            ['employee_code' => 'EMP0001', 'tanggal' => $tgl(4), 'check_in' => $tgl(4).' 09:00:00', 'check_out' => $tgl(4).' 18:00:00', 'latitude' => -7.972500, 'longitude' => 112.606900],
            ['employee_code' => 'EMP0012', 'tanggal' => $tgl(4), 'check_in' => $tgl(4).' 09:12:00', 'check_out' => $tgl(4).' 18:00:00', 'latitude' => -7.975000, 'longitude' => 112.609800],
            ['employee_code' => 'EMP0010', 'tanggal' => $tgl(4), 'check_in' => $tgl(4).' 12:20:00', 'check_out' => $tgl(4).' 21:00:00', 'latitude' => -7.972700, 'longitude' => 112.606800],

            // ===== Sabtu (jam kerja lebih pendek) =====
            ['employee_code' => 'EMP0001', 'tanggal' => $tgl(5), 'check_in' => $tgl(5).' 08:55:00', 'check_out' => $tgl(5).' 16:00:00', 'latitude' => -7.972400, 'longitude' => 112.606900],
            ['employee_code' => 'EMP0010', 'tanggal' => $tgl(5), 'check_in' => $tgl(5).' 12:05:00', 'check_out' => $tgl(5).' 18:00:00', 'latitude' => -7.975500, 'longitude' => 112.610000],
            ['employee_code' => 'EMP0013', 'tanggal' => $tgl(5), 'check_in' => $tgl(5).' 09:20:00', 'check_out' => $tgl(5).' 16:00:00', 'latitude' => -7.972650, 'longitude' => 112.606950],
        ];

        foreach ($attendances as $row) {
            $employee = Employee::where('employee_code', $row['employee_code'])->firstOrFail();

            $tanggal = Carbon::parse($row['tanggal']);
            $hari = Shift::keyHari($tanggal);
            $checkInCarbon = Carbon::parse($row['check_in']);

            $shift = Shift::determineForCheckIn($checkInCarbon, $hari);

            $result = $shift
                ? $shift->determineStatus($checkInCarbon)
                : ['status' => 'alpa', 'late_minutes' => 0];

            $lat = $row['latitude'] ?? null;
            $lng = $row['longitude'] ?? null;

            $distanceM = ($lat !== null && $lng !== null)
                ? $this->haversineMeters($branchLatitude, $branchLongitude, $lat, $lng)
                : null;

            Attendance::create([
                'employee_id' => $employee->id,
                'branch_id' => $branch->id,
                'shift_id' => $shift?->id,
                'tanggal' => $row['tanggal'],
                'check_in' => $row['check_in'],
                'check_out' => $row['check_out'],
                'status' => $result['status'],
                'late_minutes' => $result['late_minutes'],
                'activity' => $row['activity'] ?? null,
                'check_in_lat' => $lat,
                'check_in_lng' => $lng,
                'distance_m' => $distanceM,
                'check_in_photo_url' => $dummyCheckInPhoto,
                'check_out_photo_url' => $dummyCheckOutPhoto,
            ]);
        }
    }

    /**
     * Hitung jarak antara dua koordinat (meter) pakai formula Haversine.
     */
    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    private function ensureDummyPhoto(string $path, string $label): string
    {
        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        $width = 800;
        $height = 600;

        $image = imagecreatetruecolor($width, $height);

        $bg = imagecolorallocate($image, 60, 120, 160);
        $white = imagecolorallocate($image, 255, 255, 255);

        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        $fontSize = 5;

        $textWidth = imagefontwidth($fontSize) * strlen($label);

        $x = (int) (($width - $textWidth) / 2);
        $y = (int) ($height / 2);

        imagestring($image, $fontSize, $x, $y, $label, $white);

        ob_start();

        imagejpeg($image, null, 85);

        $contents = ob_get_clean();

        imagedestroy($image);

        Storage::disk('public')->put($path, $contents);

        return $path;
    }
}