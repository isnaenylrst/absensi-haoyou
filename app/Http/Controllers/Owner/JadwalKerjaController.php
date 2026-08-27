<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class JadwalKerjaController extends Controller
{
    private const HARI_KERJA = [
        'senin'  => 'Senin',
        'selasa' => 'Selasa',
        'rabu'   => 'Rabu',
        'kamis'  => 'Kamis',
        'jumat'  => 'Jumat',
        'sabtu'  => 'Sabtu',
    ];

    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * Halaman utama Jadwal Kerja.
     */
    public function __invoke(Request $request)
    {
        $filters = $request->only([
            'q',
            'tanggal_mulai',
            'tanggal_akhir',
            'branch_id',
            'employee_type',
            'status',
        ]);

        $bulanTerpilih = (int) $request->input('bulan', now()->month);
        $tahunTerpilih = (int) $request->input('tahun', now()->year);

        return view('owner.jadwal.index', [
            'hariKerja'   => array_values(self::HARI_KERJA),
            'shifts'      => $this->getShifts(),
            'attendances' => $this->getAttendances($filters),
            'branches'    => Branch::orderBy('name')->get(),
            'filters'     => $filters,
            'riwayat'     => $this->getRiwayatAbsensi($bulanTerpilih, $tahunTerpilih),
            'bulanTerpilih' => $bulanTerpilih,
            'tahunTerpilih' => $tahunTerpilih,
            'daftarBulan'   => self::NAMA_BULAN,
            'daftarTahun'   => $this->getDaftarTahun(),
        ]);
    }

    /**
     * Simpan shift baru.
     */
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

    /**
     * Card shift (ringkasan shift + jumlah karyawan hadir hari ini).
     */
    private function getShifts(): Collection
    {
        $hariIni = Shift::keyHari(Carbon::today());

        $attendanceHariIni = Attendance::query()
            ->whereDate('tanggal', Carbon::today())
            ->whereNotNull('shift_id')
            ->get();

        return Shift::applicableTo($hariIni)
            ->map(function (Shift $shift) use ($attendanceHariIni) {
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

    /**
     * Format hari berlaku, contoh: Senin, Selasa, Rabu, Kamis, Jumat -> Senin–Jumat
     */
    private function formatHariBerlaku(array $applicableDays): string
    {
        $urutan = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
        $label = [
            'senin' => 'Senin', 'selasa' => 'Selasa', 'rabu' => 'Rabu',
            'kamis' => 'Kamis', 'jumat' => 'Jumat', 'sabtu' => 'Sabtu', 'minggu' => 'Minggu',
        ];

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

    /**
     * ================================================================
     * APPROVAL PRESENSI
     * ================================================================
     */
    private function getAttendances(array $filters)
    {
        // Tanggal yang dipakai untuk daftar approval harian.
        // Prioritas: tanggal_mulai -> tanggal_akhir -> hari ini.
        $tanggalDipilih = $filters['tanggal_mulai']
            ?? $filters['tanggal_akhir']
            ?? now()->toDateString();

        $employees = Employee::query()
            ->where('employee_type', 'tetap')
            ->with('branch')
            ->when($filters['branch_id'] ?? null, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('full_name', 'like', "%{$search}%"))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        // Ambil semua attendance karyawan tetap di tanggal terpilih,
        // dikelompokkan per employee_id supaya gampang dicocokkan.
        $attendancesHariIni = Attendance::query()
            ->with('shift')
            ->whereDate('tanggal', $tanggalDipilih)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        $employees->getCollection()->transform(function (Employee $employee) use ($attendancesHariIni, $tanggalDipilih) {
            $attendance = $attendancesHariIni->get($employee->id);

            // Belum absen sama sekali di tanggal ini -> isi placeholder '-'.
            if (! $attendance) {
                return (object) [
                    'id' => null,
                    'employee' => $employee,
                    'tanggal' => Carbon::parse($tanggalDipilih),
                    'shift' => null,
                    'check_in' => null,
                    'check_out' => null,
                    'check_in_lat' => null,
                    'check_in_lng' => null,
                    'check_in_photo_url' => null,
                    'check_out_photo_url' => null,
                    'distance_m' => null,
                    'status' => null,
                    'status_label' => '-',
                    'late_label' => '-',
                    'late_minutes' => 0,
                    'sudah_absen' => false,
                ];
            }

            $lateLabel = 'Tepat waktu';
            $lateMinutes = 0;

            $startTime = $attendance->shift?->start_time;

            if ($attendance->check_in && $startTime) {
                $date = $attendance->tanggal instanceof Carbon
                    ? $attendance->tanggal->format('Y-m-d')
                    : Carbon::parse($attendance->tanggal)->format('Y-m-d');

                $scheduledTime = Carbon::parse($date.' '.$startTime);
                $checkInTime = Carbon::parse($attendance->check_in);

                if ($checkInTime->greaterThan($scheduledTime)) {
                    $minutes = $scheduledTime->diffInMinutes($checkInTime);
                    $lateMinutes = $minutes;

                    $hours = intdiv($minutes, 60);
                    $remainingMinutes = $minutes % 60;
                    $parts = [];

                    if ($hours > 0) {
                        $parts[] = $hours.' jam';
                    }

                    if ($remainingMinutes > 0) {
                        $parts[] = $remainingMinutes.' menit';
                    }

                    $lateLabel = 'Terlambat '.implode(' ', $parts);
                }
            }

            $attendance->employee = $employee;
            $attendance->status_label = ucfirst(str_replace('_', ' ', $attendance->status ?? '-'));
            $attendance->late_label = $lateLabel;
            $attendance->late_minutes = $lateMinutes;
            $attendance->sudah_absen = true;

            return $attendance;
        });

        return $employees;
    }

    /**
     * ================================================================
     * RIWAYAT ABSENSI BULANAN
     * ================================================================
     */
    private function getRiwayatAbsensi(int $bulan, int $tahun): Collection
    {
        $employees = Employee::query()
            ->where('employee_type', 'tetap')
            ->with('branch')
            ->orderBy('full_name')
            ->get();

        $attendancesBulanIni = Attendance::query()
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->with('shift')
            ->orderByDesc('tanggal')
            ->get()
            ->groupBy('employee_id');

        return $employees->map(function (Employee $employee) use ($attendancesBulanIni) {
            $records = $attendancesBulanIni->get($employee->id, collect());

            $detail = $records->map(function (Attendance $attendance) {
                return (object) [
                    'tanggal' => $attendance->tanggal,
                    'jam_masuk' => $attendance->check_in,
                    'jam_keluar' => $attendance->check_out,
                    'status' => $attendance->status,
                    'jadwal' => $attendance->shift?->name ?? '—',
                ];
            })->values();

            return (object) [
                'id' => $employee->id,
                'nama' => $employee->full_name,
                'tipe' => $employee->employee_type,
                'cabang' => $employee->branch->name ?? '—',
                'hadir' => $records->where('status', 'tepat_waktu')->count(),
                'telat' => $records->where('status', 'terlambat')->count(),
                'alpa' => $records->where('status', 'alpa')->count(),
                'detail' => $detail,
            ];
        });
    }

    /**
     * ================================================================
     * DAFTAR TAHUN (untuk dropdown filter)
     * ================================================================
     */
    private function getDaftarTahun(): array
    {
        $tahunTertua = Attendance::min('tanggal');
        $tahunAwal = $tahunTertua ? Carbon::parse($tahunTertua)->year : now()->year;

        return range(now()->year, $tahunAwal);
    }

    /**
     * ================================================================
     * PRESENSI BULANAN (per karyawan tetap)
     * ================================================================
     */
    public function presensiBulanan(Request $request, Employee $employee)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $employee->load('branch');

        $attendances = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->with('shift')
            ->orderBy('tanggal')
            ->orderBy('check_in')
            ->get();

        $attendancesPerTanggal = $attendances->groupBy(
            fn ($attendance) => Carbon::parse($attendance->tanggal)->format('Y-m-d')
        );

        $awalBulan = Carbon::create($tahun, $bulan, 1);
        $akhirBulan = $awalBulan->copy()->endOfMonth();

        $hariDalamBulan = collect();

        for ($periode = $awalBulan->copy(); $periode->lte($akhirBulan); $periode->addDay()) {
            if ($periode->isSunday()) {
                continue;
            }

            $tanggalKey = $periode->format('Y-m-d');
            $attendanceHariIni = $attendancesPerTanggal->get($tanggalKey, collect());
            $attendance = $attendanceHariIni->first();

            $jadwalLabel = $attendance
                ? ($attendance->shift?->name ?? '—')
                : '—';

            $distance = $attendanceHariIni
                ->pluck('distance_m')
                ->filter(fn ($distance) => $distance !== null)
                ->max();

            $isOutOfRadius = $distance !== null && $distance > 100;

            $statusLabel = null;
            $statusClass = null;

            if ($attendanceHariIni->isNotEmpty()) {
                $statuses = $attendanceHariIni->pluck('status')->unique();

                if ($statuses->contains('terlambat')) {
                    $statusLabel = 'Terlambat';
                    $statusClass = 'badge-rust';
                } elseif ($statuses->contains('tepat_waktu')) {
                    $statusLabel = 'Tepat Waktu';
                    $statusClass = 'badge-green';
                } else {
                    $statusLabel = ucfirst(str_replace('_', ' ', $attendance->status));
                    $statusClass = 'badge-gray';
                }
            }

            $detailSesi = $attendanceHariIni->map(function (Attendance $attendance) {
                $startTime = $attendance->shift?->start_time;
                $lateMinutes = 0;

                if ($attendance->check_in && $startTime) {
                    $tanggal = Carbon::parse($attendance->tanggal)->format('Y-m-d');
                    $scheduled = Carbon::parse($tanggal.' '.$startTime);
                    $checkIn = Carbon::parse($attendance->check_in);

                    if ($checkIn->greaterThan($scheduled)) {
                        $lateMinutes = $scheduled->diffInMinutes($checkIn);
                    }
                }

                return (object) [
                    'id' => $attendance->id,
                    'jam_masuk' => $attendance->check_in,
                    'jam_keluar' => $attendance->check_out,
                    'status' => $attendance->status,
                    'jadwal' => $attendance->shift?->name ?? '—',
                    'jam_jadwal' => $startTime,
                    'late_minutes' => $lateMinutes,
                    'distance' => $attendance->distance_m,
                ];
            })->values();

            $hariDalamBulan->push((object) [
                'minggu_ke' => (int) ceil($periode->day / 7),
                'tanggal' => $periode->copy(),
                'attendance' => $attendance,
                'attendances' => $attendanceHariIni,
                'jadwal' => $jadwalLabel,
                'detail_sesi' => $detailSesi,
                'distance' => $distance,
                'is_out_of_radius' => $isOutOfRadius,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
            ]);
        }

        $mingguan = $hariDalamBulan->groupBy('minggu_ke');

        $summary = [
            'tepat_waktu' => $attendances->where('status', 'tepat_waktu')->count(),
            'terlambat' => $attendances->where('status', 'terlambat')->count(),
            'luar_radius' => $attendances->filter(
                fn ($a) => $a->distance_m !== null && $a->distance_m > 100
            )->count(),
            'alpa' => $attendances->where('status', 'alpa')->count(),
        ];

        return view('owner.jadwal.presensi-bulanan', [
            'employee' => $employee,
            'mingguan' => $mingguan,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'summary' => $summary,
            'daftarBulan' => self::NAMA_BULAN,
            'daftarTahun' => $this->getDaftarTahun(),
        ]);
    }
}