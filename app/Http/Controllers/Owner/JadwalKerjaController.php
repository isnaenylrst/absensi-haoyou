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
use Illuminate\Pagination\LengthAwarePaginator;

class JadwalKerjaController extends Controller
{
    private array $attendanceSummary = [
        'tepat_waktu' => 0,
        'terlambat' => 0,
        'luar_radius' => 0,
        'alpa' => 0,
    ];

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

        return view('owner.jadwalkaryawan', [
            'hariKerja'   => array_values(self::HARI_KERJA),
            'shifts'      => $this->getShifts(),
            'attendances' => $this->getAttendances($filters),
            'summary'     => $this->attendanceSummary, // <-- tambahan, WAJIB dipanggil SETELAH getAttendances()
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
        $tanggalMulai = Carbon::parse($filters['tanggal_mulai'] ?? now()->toDateString());
        $tanggalAkhir = Carbon::parse($filters['tanggal_akhir'] ?? $tanggalMulai->toDateString());

        if ($tanggalAkhir->lt($tanggalMulai)) {
            [$tanggalMulai, $tanggalAkhir] = [$tanggalAkhir, $tanggalMulai];
        }

        $employees = Employee::query()
            ->where('employee_type', 'tetap')
            ->with('branch')
            ->when($filters['branch_id'] ?? null, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('full_name', 'like', "%{$search}%"))
            ->orderBy('full_name')
            ->get();

        $attendancesByEmployeeDate = Attendance::query()
            ->with('shift')
            ->whereBetween('tanggal', [$tanggalMulai->toDateString(), $tanggalAkhir->toDateString()])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy(fn ($a) => $a->employee_id.'|'.Carbon::parse($a->tanggal)->format('Y-m-d'));

        $statusFilter = $filters['status'] ?? null;
        $hariIni = now()->toDateString();

        $rows = collect();

        for ($tanggal = $tanggalMulai->copy(); $tanggal->lte($tanggalAkhir); $tanggal->addDay()) {
            if ($tanggal->isSunday()) {
                continue; // hari libur mingguan, konsisten dengan presensiBulanan()
            }

            $tanggalKey = $tanggal->toDateString();

            foreach ($employees as $employee) {
                $attendance = $attendancesByEmployeeDate->get($employee->id.'|'.$tanggalKey)?->first();

                if (! $attendance) {
                    $row = (object) [
                        'id' => null,
                        'employee' => $employee,
                        'tanggal' => $tanggal->copy(),
                        'shift' => null,
                        'check_in' => null,
                        'check_out' => null,
                        'check_in_lat' => null,
                        'check_in_lng' => null,
                        'check_in_photo_url' => null,
                        'check_out_photo_url' => null,
                        'distance_m' => null,
                        'status' => null,
                        'status_label' => $tanggalKey >= $hariIni ? 'Belum melakukan absensi' : 'Tidak melakukan absensi',
                        'late_label' => '-',
                        'late_minutes' => 0,
                        'sudah_absen' => false,
                    ];
                } else {
                    $lateLabel = 'Tepat waktu';
                    $lateMinutes = 0;
                    $startTime = $attendance->shift?->start_time;

                    if ($attendance->check_in && $startTime) {
                        $date = Carbon::parse($attendance->tanggal)->format('Y-m-d');
                        $scheduledTime = Carbon::parse($date.' '.$startTime);
                        $checkInTime = Carbon::parse($attendance->check_in);

                        if ($checkInTime->greaterThan($scheduledTime)) {
                            $minutes = $scheduledTime->diffInMinutes($checkInTime);
                            $lateMinutes = $minutes;
                            $hours = intdiv($minutes, 60);
                            $remainingMinutes = $minutes % 60;
                            $parts = [];
                            if ($hours > 0) $parts[] = $hours.' jam';
                            if ($remainingMinutes > 0) $parts[] = $remainingMinutes.' menit';
                            $lateLabel = 'Terlambat '.implode(' ', $parts);
                        }
                    }

                    $attendance->employee = $employee;
                    $attendance->status_label = ucfirst(str_replace('_', ' ', $attendance->status ?? '-'));
                    $attendance->late_label = $lateLabel;
                    $attendance->late_minutes = $lateMinutes;
                    $attendance->sudah_absen = true;

                    $row = $attendance;
                }

                // Terapkan filter status di sini, setelah status/placeholder ditentukan.
                if ($statusFilter === 'luar_radius' && ! ($row->distance_m !== null && $row->distance_m > 100)) {
                    continue;
                }
                if ($statusFilter === 'belum_absen' && $row->sudah_absen) {
                    continue;
                }
                if ($statusFilter && ! in_array($statusFilter, ['luar_radius', 'belum_absen'], true) && $row->status !== $statusFilter) {
                    continue;
                }

                $rows->push($row);
            }
        }

        $rows = $rows->sortBy('tanggal')->values();

        $this->attendanceSummary = [
            'tepat_waktu' => $rows->where('status', 'tepat_waktu')->count(),
            'terlambat' => $rows->where('status', 'terlambat')->count(),
            'luar_radius' => $rows->filter(
                fn ($row) => $row->distance_m !== null && $row->distance_m > 100
            )->count(),
            'alpa' => $rows->where('status', 'alpa')->count(),
        ];

        $perPage = 15;
        $page = (int) request()->input('page', 1);

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
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
}