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
        'senin' => 'Senin',
        'selasa' => 'Selasa',
        'rabu' => 'Rabu',
        'kamis' => 'Kamis',
        'jumat' => 'Jumat',
        'sabtu' => 'Sabtu',
    ];

    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function __invoke(Request $request)
    {
        $filters = $request->only(['q', 'tanggal_mulai', 'tanggal_akhir', 'branch_id', 'employee_type', 'status']);

        $bulanTerpilih = (int) $request->input('bulan', now()->month);
        $tahunTerpilih = (int) $request->input('tahun', now()->year);

        return view('owner.jadwal.index', [
            'hariKerja' => array_values(self::HARI_KERJA),
            'shifts' => $this->getShifts(),
            'jadwalPartTime' => $this->getJadwalPartTime(),
            'attendances' => $this->getAttendances($filters),
            'branches' => Branch::orderBy('name')->get(),
            'filters' => $filters,

            // Riwayat absensi bulanan
            'riwayat' => $this->getRiwayatAbsensi($bulanTerpilih, $tahunTerpilih),
            'bulanTerpilih' => $bulanTerpilih,
            'tahunTerpilih' => $tahunTerpilih,
            'daftarBulan' => self::NAMA_BULAN,
            'daftarTahun' => $this->getDaftarTahun(),
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

    private function getJadwalPartTime(): Collection
    {
        $employees = Employee::query()
            ->where('can_submit_teaching_sessions', true)
            ->with('partTimeSchedules')
            ->orderBy('full_name')
            ->get();

        return $employees->map(function (Employee $employee) {
            $totalSesi = 0;
            $totalMenit = 0;
            $sesi = $employee->partTimeSchedules
                ->filter(fn ($s) => $s->tanggal !== null)
                ->sortBy(fn ($s) => $s->tanggal->format('Y-m-d').' '.$s->start_time)
                ->map(fn ($s) => (object) [
                    'tanggal' => $s->tanggal,
                    'jam_mulai' => Carbon::parse($s->start_time)->format('H:i'),
                    'jam_selesai' => Carbon::parse($s->end_time)->format('H:i'),
                    'kegiatan' => $s->activity,
                ])
                ->values();

            $sesiMingguan = $sesi->groupBy(fn ($s) => $s->tanggal->copy()->startOfWeek()->format('Y-m-d'))
                ->map(function ($sesiMinggu) {
                    return $sesiMinggu->groupBy(fn ($s) => $s->tanggal->format('Y-m-d'))
                        ->map(fn ($sesiHari) => (object) [
                            'tanggal' => $sesiHari->first()->tanggal,
                            'sesi' => $sesiHari->values(),
                        ])
                        ->sortKeys();
                });

            foreach ($employee->partTimeSchedules as $s) {
                $totalMenit += Carbon::parse($s->start_time)->diffInMinutes(Carbon::parse($s->end_time));
            }
            $totalSesi = $sesi->count();

            return (object) [
                'employee_id' => $employee->id,
                'nama' => $employee->full_name,
                'jabatan' => $employee->position,
                'rate_per_jam' => $employee->partTimeSchedules->max('hourly_rate') ?? 0,
                'sesi' => $sesi,
                'sesi_mingguan' => $sesiMingguan,
                'total_sesi' => $totalSesi,
                'total_jam' => round($totalMenit / 60, 1),
            ];
        });
    }

    /** Tabel Approval Presensi — dipindah dari ApprovalController. */
    private function getAttendances(array $filters)
    {
        $tanggalMulai = $filters['tanggal_mulai'] ?? null;
        $tanggalAkhir = $filters['tanggal_akhir'] ?? null;

        $attendances = Attendance::query()
            ->with(['employee.branch', 'shift', 'partTimeSchedule'])
            ->whereHas('employee', fn ($query) => $query->where('employee_type', 'tetap'))
            ->when($tanggalMulai || $tanggalAkhir, function ($query) use ($tanggalMulai, $tanggalAkhir) {
                // Kalau cuma salah satu diisi, pakai tanggal itu untuk kedua batas
                $mulai = $tanggalMulai ?: $tanggalAkhir;
                $akhir = $tanggalAkhir ?: $tanggalMulai;

                // Jaga-jaga kalau urutannya kebalik (mulai > akhir)
                if ($mulai > $akhir) {
                    [$mulai, $akhir] = [$akhir, $mulai];
                }

                $query->whereBetween('tanggal', [$mulai, $akhir]);
            }, function ($query) {
                $query->whereDate('tanggal', now()->toDateString());
            })
            ->when($filters['branch_id'] ?? null, function ($query, $branchId) {
                $query->where('branch_id', $branchId);
            })
            ->when($filters['employee_type'] ?? null, function ($query, $type) {
                $query->whereHas('employee', function ($q) use ($type) {
                    $q->where('employee_type', $type);
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('tanggal')
            ->orderByDesc('check_in')
            ->paginate(15)
            ->withQueryString();

        $attendances->getCollection()->transform(function ($attendance) {
            $attendance->late_label = 'Tepat waktu';
            $attendance->late_minutes = 0;

            if (!$attendance->check_in) {
                return $attendance;
            }

            $startTime = $attendance->shift?->start_time
                ?? $attendance->partTimeSchedule?->start_time;

            if (!$startTime) {
                return $attendance;
            }

            $date = $attendance->tanggal instanceof Carbon
                ? $attendance->tanggal->format('Y-m-d')
                : Carbon::parse($attendance->tanggal)->format('Y-m-d');

            $scheduledTime = Carbon::parse($date . ' ' . $startTime);
            $checkInTime = Carbon::parse($attendance->check_in);

            if ($checkInTime->greaterThan($scheduledTime)) {
                $minutes = $scheduledTime->diffInMinutes($checkInTime);
                $attendance->late_minutes = $minutes;

                $hours = intdiv($minutes, 60);
                $remainingMinutes = $minutes % 60;
                $parts = [];

                if ($hours > 0) {
                    $parts[] = $hours . ' jam';
                }
                if ($remainingMinutes > 0) {
                    $parts[] = $remainingMinutes . ' menit';
                }

                $attendance->late_label = 'Terlambat ' . implode(' ', $parts);
            }

            return $attendance;
        });

        return $attendances;
    }

    /**
     * Rekap riwayat absensi 1 bulan untuk semua karyawan (tetap & part time/guru):
     * total hadir/terlambat/alpa, plus detail harian untuk ditampilkan di modal.
     */
    private function getRiwayatAbsensi(int $bulan, int $tahun): Collection
    {
        $employees = Employee::query()
            ->with('branch')
            ->orderBy('full_name')
            ->get();

        $attendancesBulanIni = Attendance::query()
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->with(['shift', 'partTimeSchedule'])
            ->orderByDesc('tanggal')
            ->get()
            ->groupBy('employee_id');

        return $employees->map(function (Employee $employee) use ($attendancesBulanIni) {
            $records = $attendancesBulanIni->get($employee->id, collect());
            $isTetap = $employee->employee_type === 'tetap';

            $detail = $records->map(function (Attendance $attendance) use ($isTetap) {
                return (object) [
                    'tanggal' => $attendance->tanggal,
                    'jam_masuk' => $attendance->check_in,
                    'jam_keluar' => $attendance->check_out,
                    'status' => $attendance->status,
                    'jadwal' => $isTetap
                        ? ($attendance->shift?->name ?? '—')
                        : ($attendance->partTimeSchedule?->activity ?? $attendance->activity ?? '—'),
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

    public function jadwalGuruBulanan(Request $request, Employee $employee)
    {
        abort_unless($employee->can_submit_teaching_sessions, 404);

        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $employee->load(['branch', 'partTimeSchedules']);
        $awalBulan = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $akhirBulan = $awalBulan->copy()->endOfMonth();
        $jadwalPerTanggal = $employee->partTimeSchedules->filter(fn ($sesi) => $sesi->tanggal !== null)->groupBy(
            fn ($sesi) => $sesi->tanggal->format('Y-m-d')
        );
        $mingguan = collect();

        for ($tanggal = $awalBulan->copy(); $tanggal->lte($akhirBulan); $tanggal->addDay()) {
            if ($tanggal->isSunday()) {
                continue;
            }

            $mingguKe = (int) ceil($tanggal->day / 7);
            $mingguan->put($mingguKe, $mingguan->get($mingguKe, collect()));
            $mingguan->get($mingguKe)->push((object) [
                'tanggal' => $tanggal->copy(),
                'sesi' => $jadwalPerTanggal->get($tanggal->format('Y-m-d'), collect())->sortBy('start_time')->map(fn ($sesi) => (object) [
                    'jam_mulai' => Carbon::parse($sesi->start_time)->format('H:i'),
                    'jam_selesai' => Carbon::parse($sesi->end_time)->format('H:i'),
                    'kegiatan' => $sesi->activity,
                ])->values(),
            ]);
        }

        return view('owner.jadwal.jadwal-guru-bulanan', [
            'employee' => $employee,
            'mingguan' => $mingguan,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'daftarBulan' => self::NAMA_BULAN,
            'daftarTahun' => $this->getDaftarTahun(),
        ]);
    }

    /** Pilihan tahun untuk dropdown filter riwayat: tahun ini mundur ke tahun data absensi tertua. */
    private function getDaftarTahun(): array
    {
        $tahunTertua = Attendance::min('tanggal');
        $tahunAwal = $tahunTertua ? Carbon::parse($tahunTertua)->year : now()->year;

        return range(now()->year, $tahunAwal);
    }

    public function presensiBulanan(Request $request, Employee $employee)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
    
        $employee->load('branch');
        $isTetap = $employee->employee_type === 'tetap';
    
        // Ambil semua presensi karyawan ini di bulan terpilih, key by tanggal biar gampang dicocokkan per hari
        $attendances = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->with(['shift', 'partTimeSchedule'])
            ->get()
            ->keyBy(fn ($a) => $a->tanggal->format('Y-m-d'));
    
        $awalBulan = Carbon::create($tahun, $bulan, 1);
        $akhirBulan = $awalBulan->copy()->endOfMonth();
    
        $hariDalamBulan = collect();
        $periode = $awalBulan->copy();
    
        while ($periode->lte($akhirBulan)) {
            $attendance = $attendances->get($periode->format('Y-m-d'));
    
            $jadwalLabel = $isTetap
                ? ($attendance?->shift?->name ?? '—')
                : ($attendance?->partTimeSchedule?->activity ?? $attendance?->activity ?? '—');
    
            $distance = $attendance?->distance_m;
            $isOutOfRadius = $distance !== null && $distance > 100;
    
            $statusLabel = null;
            $statusClass = null;
    
            if ($attendance) {
                if ($attendance->status === 'tepat_waktu') {
                    $statusLabel = 'Tepat Waktu';
                    $statusClass = 'badge-green';
                } elseif ($attendance->status === 'terlambat') {
                    $startTime = $attendance->shift?->start_time ?? $attendance->partTimeSchedule?->start_time;
                    $label = 'Terlambat';
    
                    if ($attendance->check_in && $startTime) {
                        $scheduled = Carbon::parse($periode->format('Y-m-d') . ' ' . $startTime);
                        $checkIn = Carbon::parse($attendance->check_in);
                        $menit = $scheduled->diffInMinutes($checkIn);
                        $jam = intdiv($menit, 60);
                        $sisaMenit = $menit % 60;
                        $parts = [];
                        if ($jam > 0) $parts[] = $jam . ' jam';
                        if ($sisaMenit > 0) $parts[] = $sisaMenit . ' menit';
                        if ($parts) $label .= ' ' . implode(' ', $parts);
                    }
    
                    $statusLabel = $label;
                    $statusClass = 'badge-rust';
                } else {
                    $statusLabel = ucfirst(str_replace('_', ' ', $attendance->status));
                    $statusClass = 'badge-gray';
                }
            }
    
            if ($periode->isSunday()) {
                $periode->addDay();
                continue;
            }

            $hariDalamBulan->push((object) [
                'minggu_ke'      => (int) ceil($periode->day / 7),
                'tanggal'        => $periode->copy(),
                'attendance'     => $attendance,
                'jadwal'         => $jadwalLabel,
                'distance'       => $distance,
                'is_out_of_radius' => $isOutOfRadius,
                'status_label'   => $statusLabel,
                'status_class'   => $statusClass,
            ]);
    
            $periode->addDay();
        }
    
        $mingguan = $hariDalamBulan->groupBy('minggu_ke');
    
        $summary = [
            'tepat_waktu' => $attendances->where('status', 'tepat_waktu')->count(),
            'terlambat'   => $attendances->where('status', 'terlambat')->count(),
            'luar_radius' => $attendances->filter(fn ($a) => $a->distance_m !== null && $a->distance_m > 100)->count(),
            'alpa'        => $attendances->where('status', 'alpa')->count(),
        ];
    
        return view('owner.jadwal.presensi-bulanan', [
            'employee'    => $employee,
            'isTetap'     => $isTetap,
            'mingguan'    => $mingguan,
            'bulan'       => $bulan,
            'tahun'       => $tahun,
            'summary'     => $summary,
            'daftarBulan' => self::NAMA_BULAN,
            'daftarTahun' => $this->getDaftarTahun(),
        ]);
    }
}