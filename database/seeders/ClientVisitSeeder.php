<?php

namespace Database\Seeders;

use App\Models\ClientVisit;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ClientVisitSeeder extends Seeder
{
    /**
     * PRASYARAT: EmployeeSeeder harus sudah dijalankan lebih dulu.
     */
    public function run(): void
    {
        $dummyPhotoPath = $this->ensureDummyPhoto();

        $visits = [
            [
                'employee_code' => 'EMP0006', // Natalia Repiuli Dertina Sitorus
                'client_name' => 'Keluarga Bapak Hendra',
                'address' => 'Jl. Ijen No. 12, Malang',
                'visit_type' => 'Les Privat',
                'latitude' => -7.9797,
                'longitude' => 112.6304,
                'accuracy_m' => 8.50,
                'notes' => 'Les privat Mandarin di rumah klien, sesi ke-3.',
                'visited_at' => '2026-08-10 17:30:00',
                'review_status' => 'wajar',
            ],
            [
                'employee_code' => 'EMP0006',
                'client_name' => 'Keluarga Bapak Hendra',
                'address' => 'Jl. Ijen No. 12, Malang',
                'visit_type' => 'Les Privat',
                'latitude' => -7.9795,
                'longitude' => 112.6306,
                'accuracy_m' => 12.30,
                'notes' => 'Sesi ke-4, lokasi absen sedikit meleset dari rumah klien.',
                'visited_at' => '2026-08-13 17:35:00',
                'review_status' => 'perlu_ditinjau',
            ],
            [
                'employee_code' => 'EMP0008', // Marsya Amelia
                'client_name' => 'Ibu Susanti',
                'address' => 'Jl. Bendungan Sutami No. 5, Malang',
                'visit_type' => 'Les Privat',
                'latitude' => -7.9553,
                'longitude' => 112.6086,
                'accuracy_m' => 6.00,
                'notes' => null,
                'visited_at' => '2026-08-08 09:15:00',
                'review_status' => 'wajar',
            ],
            [
                'employee_code' => 'EMP0011', // Fitri Maulidah
                'client_name' => 'Keluarga Bapak Yusuf',
                'address' => 'Perumahan Griya Shanta Blok C.10, Malang',
                'visit_type' => 'Kunjungan Sales',
                'latitude' => null,
                'longitude' => null,
                'accuracy_m' => null,
                'notes' => 'Kunjungan rutin bulanan, koordinat tidak tercatat karena GPS mati.',
                'visited_at' => '2026-08-05 10:00:00',
                'review_status' => 'perlu_ditinjau',
            ],
            [
                'employee_code' => 'EMP0015', // Dwi Ayu Wulandari
                'client_name' => 'Sekolah Mitra ABC',
                'address' => 'Jl. Bantur No. 20, Malang',
                'visit_type' => 'Event',
                'latitude' => -8.1234,
                'longitude' => 112.5678,
                'accuracy_m' => 15.00,
                'notes' => 'Presentasi program les ke pihak sekolah.',
                'visited_at' => '2026-08-14 13:00:00',
                'review_status' => 'wajar',
            ],
            // Tambahkan kunjungan lain di sini...
        ];

        foreach ($visits as $row) {
            $employee = Employee::where('employee_code', $row['employee_code'])->firstOrFail();

            ClientVisit::create([
                'employee_id' => $employee->id,
                'client_name' => $row['client_name'],
                'address' => $row['address'],
                'visit_type' => $row['visit_type'],
                'latitude' => $row['latitude'] ?? null,
                'longitude' => $row['longitude'] ?? null,
                'accuracy_m' => $row['accuracy_m'] ?? null,
                'photo_url' => $dummyPhotoPath, // dummy dulu, belum dari kamera
                'notes' => $row['notes'] ?? null,
                'visited_at' => $row['visited_at'],
                'review_status' => $row['review_status'] ?? 'wajar',
            ]);
        }
    }

    private function ensureDummyPhoto(): string
    {
        $path = 'seed-photos/dummy-client-visit.jpg';

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        $width = 800;
        $height = 600;
        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, 60, 120, 160);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        $text = 'DUMMY PHOTO - CLIENT VISIT';
        $fontSize = 5; // built-in GD font, 1-5
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $x = (int) (($width - $textWidth) / 2);
        $y = (int) ($height / 2);
        imagestring($image, $fontSize, $x, $y, $text, $white);

        ob_start();
        imagejpeg($image, null, 85);
        $contents = ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put($path, $contents);

        return $path;
    }
}