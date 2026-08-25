<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class JadwalKerjaController extends Controller
{
    private const HARI_KERJA = [
        'senin' => 'Senin',
        'selasa' => 'Selasa',
        'rabu' => 'Rabu',
        'kamis' => 'Kamis',
        'jumat' => 'Jumat',
        'sabtu' => 'Sabtu',
    ];

    /** Palet warna avatar-dot, dipilih berputar berdasarkan urutan karyawan. */
    private const AVATAR_COLORS = ['#2E6FDB', '#E8863A', '#2F8A5B', '#D34D9C', '#8A6212', '#D34D3C'];

    public function __invoke(Request $request)
    {
        return view('owner.jadwalkerja', [
            'hariKerja' => array_values(self::HARI_KERJA),
            'shifts' => $this->getShifts(),
            'penempatanShift' => $this->getPenempatanShift(),
            'jadwalPartTime' => $this->getJadwalPartTime(),
        ]);
    }

    /** Simpan shift baru dari modal "+ Tambah Shift". */
    public function storeShift(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'applicable_days' => ['required', 'array', 'min:1'],
            'applicable_days.*' => ['in:senin,selasa,rabu,kamis,jumat,sabtu,minggu'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'tolerance_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
        ], [
            'applicable_days.required' => 'Pilih minimal 1 hari berlaku.',
            'end_time.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        Shift::create([
            'name' => $validated['name'],
            'applicable_days' => $validated['applicable_days'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'tolerance_minutes' => $validated['tolerance_minutes'] ?? 10,
        ]);

        return back()->with('success', 'Shift berhasil ditambahkan.');
    }

    /** Card Shift Pagi/Siang: hanya shift yang berlaku hari ini, jumlah karyawan dari absensi hari ini. */
    private function getShifts(): Collection
    {
        $hariIni = Shift::keyHari(Carbon::today());

        $attendanceHariIni = Attendance::query()
            ->whereDate('tanggal', Carbon::today())
            ->whereNotNull('shift_id')
            ->get();

        return Shift::applicableTo($hariIni)->map(function (Shift $shift) use ($attendanceHariIni) {
            $jumlahKaryawan = $attendanceHariIni
                ->where('shift_id', $shift->id)
                ->unique('employee_id')
                ->count();

            return (object) [
                'nama' => $shift->name,
                'jam_mulai' => Carbon::parse($shift->start_time)->format('H:i'),
                'jam_selesai' => Carbon::parse($shift->end_time)->format('H:i'),
                'toleransi_menit' => $shift->tolerance_minutes,
                'jumlah_karyawan' => $jumlahKaryawan,
                'hari_berlaku' => $this->formatHariBerlaku($shift->applicable_days),
            ];
        });
    }

    /** Ubah array hari jadi label ringkas: hari berurutan digabung jadi rentang ("Senin–Jumat"), yang tidak berurutan dipisah koma. */
    private function formatHariBerlaku(array $applicableDays): string
    {
        $urutan = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
        $label = ['senin' => 'Senin', 'selasa' => 'Selasa', 'rabu' => 'Rabu', 'kamis' => 'Kamis', 'jumat' => 'Jumat', 'sabtu' => 'Sabtu', 'minggu' => 'Minggu'];

        $indeks = collect($applicableDays)
            ->map(fn ($hari) => array_search($hari, $urutan))
            ->filter(fn ($i) => $i !== false)
            ->sort()
            ->values();

        if ($indeks->isEmpty()) {
            return '-';
        }

        $rentang = [];
        $mulai = $indeks[0];
        $sebelumnya = $indeks[0];

        foreach ($indeks->slice(1) as $i) {
            if ($i === $sebelumnya + 1) {
                $sebelumnya = $i;
                continue;
            }

            $rentang[] = [$mulai, $sebelumnya];
            $mulai = $i;
            $sebelumnya = $i;
        }
        $rentang[] = [$mulai, $sebelumnya];

        return collect($rentang)
            ->map(fn ($r) => $r[0] === $r[1]
                ? $label[$urutan[$r[0]]]
                : $label[$urutan[$r[0]]].'–'.$label[$urutan[$r[1]]])
            ->implode(', ');
    }

    /** Tabel mingguan: tiap sel dibaca dari Attendance minggu berjalan, default 'Libur' kalau belum ada absen. */
    private function getPenempatanShift(): Collection
    {
        $mingguIni = $this->getTanggalMingguIni();

        $employees = Employee::query()
            ->where('employee_type', 'tetap')
            ->with(['attendances' => function ($query) use ($mingguIni) {
                $query->whereBetween('tanggal', [reset($mingguIni), end($mingguIni)])
                      ->with('shift');
            }])
            ->orderBy('full_name')
            ->get();

        return $employees->values()->map(function (Employee $employee, int $index) use ($mingguIni) {
            $jadwal = [];

            foreach (self::HARI_KERJA as $key => $label) {
                $tanggal = $mingguIni[$key];

                $absenHariItu = $employee->attendances
                    ->first(fn ($a) => $a->tanggal->isSameDay($tanggal));

                $jadwal[$label] = $absenHariItu?->shift?->name ?? 'Libur';
            }

            return (object) [
                'nama' => $employee->full_name,
                'inisial' => $employee->initials(),
                'warna_avatar' => self::AVATAR_COLORS[$index % count(self::AVATAR_COLORS)],
                'jadwal' => $jadwal,
            ];
        });
    }

    /** Tanggal Senin s.d. Sabtu untuk minggu berjalan, key-nya samain sama HARI_KERJA. */
    private function getTanggalMingguIni(): array
    {
        $senin = Carbon::now()->startOfWeek(Carbon::MONDAY);

        $tanggal = [];
        foreach (array_keys(self::HARI_KERJA) as $index => $key) {
            $tanggal[$key] = $senin->copy()->addDays($index);
        }

        return $tanggal;
    }

    private function getJadwalPartTime(): Collection
    {
        $employees = Employee::query()
            ->where('can_submit_teaching_sessions', true)
            ->with('partTimeSchedules')
            ->orderBy('full_name')
            ->get();

        return $employees->map(function (Employee $employee) {
            $sesi = [];
            $totalSesi = 0;
            $totalMenit = 0;

            foreach (self::HARI_KERJA as $key => $label) {
                $sesiHari = $employee->partTimeSchedules
                    ->where('day_of_week', $key)
                    ->sortBy('start_time')
                    ->map(fn ($s) => (object) [
                        'jam_mulai' => Carbon::parse($s->start_time)->format('H:i'),
                        'jam_selesai' => Carbon::parse($s->end_time)->format('H:i'),
                        'kegiatan' => $s->activity,
                    ])
                    ->values();

                $sesi[$label] = $sesiHari;
                $totalSesi += $sesiHari->count();
            }

            foreach ($employee->partTimeSchedules as $s) {
                $totalMenit += Carbon::parse($s->start_time)->diffInMinutes(Carbon::parse($s->end_time));
            }

            return (object) [
                'nama' => $employee->full_name,
                'jabatan' => $employee->position,
                'rate_per_jam' => $employee->partTimeSchedules->max('hourly_rate') ?? 0,
                'sesi' => $sesi,
                'total_sesi' => $totalSesi,
                'total_jam' => round($totalMenit / 60, 1),
            ];
        });
    }
}