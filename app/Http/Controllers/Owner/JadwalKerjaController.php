<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;

class JadwalKerjaController extends Controller
{
    private const VALID_STATUSES = ['tepat_waktu', 'terlambat', 'tidak_checkout', 'cuti', 'alpa'];

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

    public function __invoke(Request $request)
    {
        $filters = $request->only([
            'q',
            'branch_id',
            'employee_type',
        ]);

        $bulanTerpilih = (int) $request->input('bulan', now()->month);
        $tahunTerpilih = (int) $request->input('tahun', now()->year);

        return view('owner.jadwalkaryawan', [
            'hariKerja'     => array_values(self::HARI_KERJA),
            'shifts'        => $this->getShifts(),
            'allShifts'     => $this->getAllShifts(),
            'summaryCards'  => $this->getCategorySummary($filters, $bulanTerpilih, $tahunTerpilih),
            'branches'      => Branch::orderBy('name')->get(),
            'filters'       => $filters,
            'riwayat'       => $this->getRiwayatAbsensi($bulanTerpilih, $tahunTerpilih),
            'bulanTerpilih' => $bulanTerpilih,
            'tahunTerpilih' => $tahunTerpilih,
            'daftarBulan'   => self::NAMA_BULAN,
            'daftarTahun'   => $this->getDaftarTahun(),
            'archiveFiles'  => $this->getArchiveFiles(),
        ]);
    }

    /**
     * Download file arsip attendance (hasil export sebelum soft-delete).
     */
    public function downloadArchive(string $filename)
    {
        $path = 'archives/'.$filename;

        abort_unless(Storage::disk('local')->exists($path), 404, 'File arsip tidak ditemukan.');

        return response()->download(Storage::disk('local')->path($path));
    }

    private function getArchiveFiles(): Collection
    {
        return collect(Storage::disk('local')->files('archives'))
            ->filter(fn ($file) => str_ends_with($file, '.xlsx'))
            ->map(function ($file) {
                return (object) [
                    'name' => basename($file),
                    'size' => round(Storage::disk('local')->size($file) / 1024, 2).' KB',
                    'date' => Carbon::createFromTimestamp(
                        Storage::disk('local')->lastModified($file)
                    )->format('d M Y H:i'),
                ];
            })
            ->sortByDesc('date')
            ->values();
    }

    public function presensiKategori(Request $request, string $kategori)
    {
        abort_unless(in_array($kategori, ['masuk', 'cuti', 'alpa'], true), 404);

        $filters = $request->only([
            'q', 'branch_id', 'status', 'mode', 'tanggal_mulai', 'tanggal_akhir', 'bulan', 'tahun',
        ]);

        [$tanggalMulai, $tanggalAkhir] = $this->resolveDateRange($filters);
        $employees = $this->resolveEmployeesForFilters($filters);
        $allRows = $this->buildAttendanceRows($tanggalMulai, $tanggalAkhir, $employees);

        $rows = $allRows->filter(function ($row) use ($kategori) {
            return match ($kategori) {
                'masuk' => $row->check_in !== null,
                'cuti' => $row->status === 'cuti',
                'alpa' => $row->status === 'alpa' || ! $row->sudah_absen,
            };
        })->values();

        $statusFilter = $filters['status'] ?? null;
        if ($kategori === 'masuk' && $statusFilter) {
            $rows = $rows->filter(function ($row) use ($statusFilter) {
                if ($statusFilter === 'luar_radius') {
                    return $row->distance_m !== null && $row->distance_m > 100;
                }

                return $row->status === $statusFilter;
            })->values();
        }

        $perPage = 15;
        $page = (int) $request->input('page', 1);

        $attendances = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('owner.presensikategori', [
            'kategori'      => $kategori,
            'attendances'   => $attendances,
            'filters'       => $filters,
            'branches'      => Branch::orderBy('name')->get(),
            'daftarBulan'   => self::NAMA_BULAN,
            'daftarTahun'   => $this->getDaftarTahun(),
            'tanggalMulai'  => $tanggalMulai,
            'tanggalAkhir'  => $tanggalAkhir,
        ]);
    }

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

    public function updateShift(Request $request, Shift $shift)
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

        $shift->update([
            'name' => $validated['name'],
            'applicable_days' => $validated['applicable_days'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'tolerance_minutes' => $validated['tolerance_minutes'] ?? 10,
        ]);

        return back()->with('success', 'Shift berhasil diperbarui.');
    }

    public function updateAttendanceStatus(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::VALID_STATUSES)],
        ], [
            'status.in' => 'Status tidak valid.',
        ]);

        $attendance->update([
            'status' => $validated['status'],
            'manual_override' => true,
        ]);

        return back()->with('success', 'Status presensi '.$attendance->employee->full_name.' berhasil diperbarui secara manual.');
    }

    public function overrideAttendanceForDate(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'tanggal' => ['required', 'date'],
            'status' => ['required', 'in:'.implode(',', self::VALID_STATUSES)],
        ], [
            'status.in' => 'Status tidak valid.',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        Attendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'tanggal' => $validated['tanggal'],
            ],
            [
                'branch_id' => $employee->branch_id,
                'status' => $validated['status'],
                'manual_override' => true,
            ]
        );

        return back()->with('success', 'Status presensi '.$employee->full_name.' berhasil diisi secara manual.');
    }

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
                    'id' => $shift->id,
                    'nama' => $shift->name,
                    'jam_mulai' => Carbon::parse($shift->start_time)->format('H:i'),
                    'jam_selesai' => Carbon::parse($shift->end_time)->format('H:i'),
                    'toleransi_menit' => $shift->tolerance_minutes,
                    'jumlah_karyawan' => $jumlahKaryawan,
                    'hari_berlaku' => $this->formatHariBerlaku($shift->applicable_days),
                ];
            });
    }

    private function getAllShifts(): Collection
    {
        return Shift::orderBy('start_time')
            ->get()
            ->map(function (Shift $shift) {
                return (object) [
                    'id' => $shift->id,
                    'nama' => $shift->name,
                    'jam_mulai' => Carbon::parse($shift->start_time)->format('H:i'),
                    'jam_selesai' => Carbon::parse($shift->end_time)->format('H:i'),
                    'toleransi_menit' => $shift->tolerance_minutes,
                    'applicable_days' => $shift->applicable_days,
                ];
            });
    }

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

    private function resolveDateRange(array $filters): array
    {
        if (($filters['mode'] ?? null) === 'bulanan' && ! empty($filters['bulan']) && ! empty($filters['tahun'])) {
            $awal = Carbon::create((int) $filters['tahun'], (int) $filters['bulan'], 1)->startOfMonth();

            return [$awal, $awal->copy()->endOfMonth()];
        }

        $tanggalMulai = Carbon::parse($filters['tanggal_mulai'] ?? now()->toDateString());
        $tanggalAkhir = Carbon::parse($filters['tanggal_akhir'] ?? $tanggalMulai->toDateString());

        if ($tanggalAkhir->lt($tanggalMulai)) {
            [$tanggalMulai, $tanggalAkhir] = [$tanggalAkhir, $tanggalMulai];
        }

        return [$tanggalMulai, $tanggalAkhir];
    }

    private function resolveEmployeesForFilters(array $filters): Collection
    {
        return Employee::query()
            ->where('employee_type', 'tetap')
            ->with('branch')
            ->when($filters['branch_id'] ?? null, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('full_name', 'like', "%{$search}%"))
            ->orderBy('full_name')
            ->get();
    }

    private function buildAttendanceRows(Carbon $tanggalMulai, Carbon $tanggalAkhir, Collection $employees): Collection
    {
        $attendancesByEmployeeDate = Attendance::query()
            ->with('shift')
            ->whereBetween('tanggal', [$tanggalMulai->toDateString(), $tanggalAkhir->toDateString()])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy(fn ($a) => $a->employee_id.'|'.Carbon::parse($a->tanggal)->format('Y-m-d'));

        $approvedLeavesByEmployee = LeaveRequest::query()
            ->where('status', 'disetujui')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('start_date', '<=', $tanggalAkhir->toDateString())
            ->whereDate('end_date', '>=', $tanggalMulai->toDateString())
            ->get()
            ->groupBy('employee_id');

        $hariIni = now()->toDateString();
        $allRows = collect();

        for ($tanggal = $tanggalMulai->copy(); $tanggal->lte($tanggalAkhir); $tanggal->addDay()) {
            if ($tanggal->isSunday()) {
                continue; // hari libur mingguan, konsisten dengan presensiBulanan()
            }

            $tanggalKey = $tanggal->toDateString();
            $tanggalSudahLewat = $tanggalKey < $hariIni;

            foreach ($employees as $employee) {
                $attendance = $attendancesByEmployeeDate->get($employee->id.'|'.$tanggalKey)?->first();

                if (! $attendance) {
                    // Cek apakah tanggal ini masuk rentang cuti/izin yang sudah disetujui.
                    $leave = $approvedLeavesByEmployee->get($employee->id, collect())
                        ->first(function ($lr) use ($tanggalKey) {
                            return $tanggalKey >= Carbon::parse($lr->start_date)->toDateString()
                                && $tanggalKey <= Carbon::parse($lr->end_date)->toDateString();
                        });

                    if ($leave) {
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
                            'status' => 'cuti',
                            'status_label' => 'Cuti',
                            'leave_type' => $leave->leave_type,
                            'activity' => $leave->reason,
                            'late_label' => '-',
                            'late_minutes' => 0,
                            'manual_override' => false,
                            // Dianggap "sudah ada keterangan" -> tidak masuk hitungan alpa.
                            'sudah_absen' => true,
                        ];
                    } else {
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
                            'status' => $tanggalSudahLewat ? 'alpa' : null,
                            'status_label' => $tanggalSudahLewat ? 'Tidak melakukan absensi' : 'Belum melakukan absensi',
                            'activity' => null,
                            'late_label' => '-',
                            'late_minutes' => 0,
                            'manual_override' => false,
                            'sudah_absen' => false,
                        ];
                    }
                } else {
                    $attendance->employee = $employee;
                    $attendance->sudah_absen = true;

                    if ($attendance->manual_override) {
                        $attendance->status_label = ucfirst(str_replace('_', ' ', $attendance->status ?? '-'));
                        $attendance->late_label = $attendance->late_minutes > 0
                            ? $this->formatLateLabel($attendance->late_minutes)
                            : ($attendance->status === 'tidak_checkout' ? 'Tidak checkout' : 'Tepat waktu');
                    } else {
                        [$status, $lateMinutes, $lateLabel] = $this->resolveStatus($attendance, $tanggalSudahLewat);

                        $attendance->status = $status;
                        $attendance->status_label = ucfirst(str_replace('_', ' ', $status));
                        $attendance->late_minutes = $lateMinutes;
                        $attendance->late_label = $lateLabel;
                    }

                    $row = $attendance;
                }

                $allRows->push($row);
            }
        }

        return $allRows->sortBy('tanggal')->values();
    }

    private function getCategorySummary(array $filters, int $bulan, int $tahun): array
    {
        $awalBulan = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $akhirBulan = $awalBulan->copy()->endOfMonth();
        $hariIni = now()->startOfDay();
        if ($hariIni->lt($akhirBulan)) {
            $akhirBulan = $hariIni;
        }

        // Kalau bulan yang dipilih ada di masa depan, tidak ada data yang bisa dihitung.
        if ($awalBulan->gt($akhirBulan)) {
            return [
                'masuk' => 0,
                'cuti'  => 0,
                'alpa'  => 0,
            ];
        }

        $employees = $this->resolveEmployeesForFilters($filters);
        $rows = $this->buildAttendanceRows($awalBulan, $akhirBulan, $employees);

        return [
            'masuk' => $rows->filter(fn ($row) => $row->check_in !== null)->count(),
            'cuti' => $rows->where('status', 'cuti')->count(),
            'alpa' => $rows->filter(fn ($row) => $row->status === 'alpa' || ! $row->sudah_absen)->count(),
        ];
    }

    private function getPresensiHariIni(array $filters): Collection
    {
        $employees = $this->resolveEmployeesForFilters($filters);

        return $this->buildAttendanceRows(Carbon::today(), Carbon::today(), $employees);
    }    

    private function resolveStatus(Attendance $attendance, bool $tanggalSudahLewat): array
    {
        if (! $attendance->check_in) {
            return $tanggalSudahLewat
                ? ['alpa', 0, '-']
                : [$attendance->status ?? 'alpa', 0, '-'];
        }

        if (! $attendance->check_out && $tanggalSudahLewat) {
            return ['tidak_checkout', (int) ($attendance->late_minutes ?? 0), 'Tidak checkout'];
        }

        $lateMinutes = $attendance->late_minutes;

        if ($lateMinutes === null) {
            $lateMinutes = 0;
            $startTime = $attendance->shift?->start_time;
            $toleransi = $attendance->shift?->tolerance_minutes ?? 0;

            if ($startTime) {
                $date = Carbon::parse($attendance->tanggal)->format('Y-m-d');
                $scheduledTime = Carbon::parse($date.' '.$startTime);
                $batasTepatWaktu = $scheduledTime->copy()->addMinutes($toleransi);
                $checkInTime = Carbon::parse($attendance->check_in);

                if ($checkInTime->greaterThan($batasTepatWaktu)) {
                    $lateMinutes = $scheduledTime->diffInMinutes($checkInTime);
                }
            }
        }

        if ($lateMinutes > 0) {
            return ['terlambat', $lateMinutes, $this->formatLateLabel($lateMinutes)];
        }

        return ['tepat_waktu', 0, 'Tepat waktu'];
    }

    private function formatLateLabel(int $lateMinutes): string
    {
        $hours = intdiv($lateMinutes, 60);
        $minutes = $lateMinutes % 60;

        $parts = [];
        if ($hours > 0) $parts[] = $hours.' jam';
        if ($minutes > 0) $parts[] = $minutes.' menit';

        return 'Terlambat '.implode(' ', $parts);
    }

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

        // Pengajuan cuti/izin disetujui yang beririsan dengan bulan terpilih, untuk hitung "cuti".
        $awalBulan = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $akhirBulan = $awalBulan->copy()->endOfMonth();

        $approvedLeavesByEmployee = LeaveRequest::query()
            ->where('status', 'disetujui')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('start_date', '<=', $akhirBulan->toDateString())
            ->whereDate('end_date', '>=', $awalBulan->toDateString())
            ->get()
            ->groupBy('employee_id');

        return $employees->map(function (Employee $employee) use ($attendancesBulanIni, $approvedLeavesByEmployee, $awalBulan, $akhirBulan) {
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

            // Hitung jumlah hari cuti (dipotong ke rentang bulan yang sedang dilihat).
            $cutiDays = $approvedLeavesByEmployee->get($employee->id, collect())
                ->sum(function ($lr) use ($awalBulan, $akhirBulan) {
                    $mulai = Carbon::parse($lr->start_date)->max($awalBulan);
                    $akhir = Carbon::parse($lr->end_date)->min($akhirBulan);

                    return $this->countHariKerja($mulai, $akhir);
                });

            return (object) [
                'id' => $employee->id,
                'nama' => $employee->full_name,
                'tipe' => $employee->employee_type,
                'cabang' => $employee->branch->name ?? '—',
                'hadir' => $records->where('status', 'tepat_waktu')->count(),
                'telat' => $records->where('status', 'terlambat')->count(),
                'tidak_checkout' => $records->where('status', 'tidak_checkout')->count(),
                'cuti' => $cutiDays,
                'alpa' => $records->where('status', 'alpa')->count(),
                'detail' => $detail,
            ];
        });
    }

    private function getDaftarTahun(): array
    {
        $tahunTertua = Attendance::min('tanggal');
        $tahunAwal = $tahunTertua ? Carbon::parse($tahunTertua)->year : now()->year;

        return range(now()->year, $tahunAwal);
    }

    private function countHariKerja(Carbon $mulai, Carbon $akhir): int
    {
        if ($mulai->gt($akhir)) {
            return 0;
        }

        $jumlah = 0;
        for ($tanggal = $mulai->copy(); $tanggal->lte($akhir); $tanggal->addDay()) {
            if (! $tanggal->isSunday()) {
                $jumlah++;
            }
        }

        return $jumlah;
    }
}