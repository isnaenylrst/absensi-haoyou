<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    /* ============================================================
     * HALAMAN PAYROLL BULANAN
     * ============================================================ */
    public function index(Request $request): View
    {
        $this->authorizeOwner();

        $periode  = $this->resolvePeriode($request);
        $settings = AttendanceSetting::current();

        $payrollPeriod = PayrollPeriod::firstOrCreate(
            [
                'period_year'  => $periode->year,
                'period_month' => $periode->month,
            ],
            ['hari_efektif' => 0]
        );

        $tetapEmployees = Employee::where('employee_type', 'tetap')
            ->with('payrollComponent')
            ->get()
            ->map(fn ($e) => $this->attachTetapSummary($e, $periode, $settings, $payrollPeriod));

        $partTimeEmployees = Employee::where('employee_type', 'part_time')
            ->with('payrollComponent')
            ->get()
            ->map(fn ($e) => $this->attachPartTimeSummary($e, $periode));

        return view('owner.payroll', [
            'tetapEmployees'    => $tetapEmployees,
            'partTimeEmployees' => $partTimeEmployees,
            'periodeLabel'      => $periode->translatedFormat('F Y'),
            'periodeValue'      => $periode->format('Y-m'),
            'isCurrentPeriode'  => $periode->isSameMonth(Carbon::now()),
            'rateMakan'         => (float) $settings->meal_rate,
            'rateBensin'        => (float) $settings->transport_rate,
            'bonusKerajinan'    => (float) ($settings->bonus_kerajinan ?? 0),
            'payrollPeriod'     => $payrollPeriod,
            'hariEfektif'       => (int) $payrollPeriod->hari_efektif,
        ]);
    }

    /* ============================================================
     * HARI EFEKTIF (tetap diinput owner setiap bulan)
     * ============================================================ */
    public function updatePeriod(Request $request): RedirectResponse
    {
        $this->authorizeOwner();

        $data = $request->validate([
            'periode'      => ['required', 'date_format:Y-m'],
            'hari_efektif' => ['required', 'integer', 'min:0', 'max:31'],
        ]);

        $periode = Carbon::createFromFormat('Y-m', $data['periode'])->startOfMonth();

        PayrollPeriod::updateOrCreate(
            [
                'period_year'  => $periode->year,
                'period_month' => $periode->month,
            ],
            ['hari_efektif' => $data['hari_efektif']]
        );

        return redirect()
            ->route('payroll.index', ['periode' => $periode->format('Y-m')])
            ->with('success', "Hari efektif periode {$periode->translatedFormat('F Y')} berhasil disimpan.");
    }

    /* ============================================================
     * PENGATURAN GAJI POKOK (MASTER) + BONUS KERAJINAN
     * ============================================================ */
    public function master(): View
    {
        $this->authorizeOwner();

        $settings = AttendanceSetting::current();

        $employees = Employee::where('employee_type', 'tetap')
            ->with('payrollComponent')
            ->orderBy('full_name')
            ->get();

        return view('owner.payroll-master', [
            'employees'      => $employees,
            'rateMakan'      => (float) $settings->meal_rate,
            'rateBensin'     => (float) $settings->transport_rate,
        ]);
    }


    /**
     * Simpan + kunci gaji pokok, atau buka kunci untuk diedit.
     */
    public function updateMaster(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeOwner();
        abort_unless($employee->employee_type === 'tetap', 404);

        $data = $request->validate([
            'action'      => ['required', 'in:save,unlock'],
            'base_salary' => ['required_if:action,save', 'nullable', 'numeric', 'min:0'],
        ]);

         $data = $request->validate([
            'action'          => ['required', 'in:save,unlock'],
            'base_salary'     => ['required_if:action,save', 'nullable', 'numeric', 'min:0'],
            'bonus_kerajinan' => ['nullable', 'numeric', 'min:0'],
            'bonus_kinerja'   => ['nullable', 'numeric', 'min:0'],
        ]);

        $component = PayrollComponent::firstOrNew(['employee_id' => $employee->id]);

        // ---- BUKA KUNCI ----
        if ($data['action'] === 'unlock') {
            if ($component->exists) {
                $component->gaji_locked = false;
                $component->save();
            }

            return back()->with('success', "Data gaji {$employee->full_name} dibuka. Silakan ubah lalu simpan.");
        }


        // ---- SIMPAN & KUNCI ----
          if ($component->exists && $component->gaji_locked) {
            return back()->withErrors([
                'gaji' => "Data gaji {$employee->full_name} masih terkunci. Klik Edit terlebih dahulu.",
            ]);
        }

        $component->base_salary     = $data['base_salary'];
        $component->bonus_kerajinan = $data['bonus_kerajinan'] ?? 0;
        $component->bonus_kinerja   = $data['bonus_kinerja'] ?? 0;
        $component->gaji_locked     = true;
        $component->effective_date  = now()->format('Y-m-d');
        $component->save();

        return back()->with('success', "Data gaji {$employee->full_name} disimpan dan dikunci.");
    }
    /* ============================================================
     * INPUT BULANAN: Bonus Kinerja + THR (tetap)
     *                Fee/Makan/Bensin (part time)
     * ============================================================ */
    public function updateComponent(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeOwner();

        $data = $request->validate([
            'thr_manual'     => ['nullable', 'numeric', 'min:0'],
            'base_salary'    => ['nullable', 'numeric', 'min:0'], // khusus part time
            'meal_rate'      => ['nullable', 'numeric', 'min:0'], // khusus part time
            'transport_rate' => ['nullable', 'numeric', 'min:0'], // khusus part time
        ]);

        /* ---------- PART TIME (tidak berubah) ---------- */
        if ($employee->employee_type === 'part_time') {

            PayrollComponent::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'base_salary'     => $data['base_salary'] ?? 0,
                    'meal_rate'       => $data['meal_rate'] ?? 0,
                    'transport_rate'  => $data['transport_rate'] ?? 0,
                    'allowance'       => 0,
                    'bonus_kerajinan' => 0,
                    'bonus_kinerja'   => 0,
                    'thr_active'      => false,
                    'effective_date'  => now()->format('Y-m-d'),
                ]
            );

            return back()->with('success', "Komponen gaji {$employee->full_name} berhasil disimpan.");
        }

        /* ---------- KARYAWAN TETAP ----------
         * Gaji pokok TIDAK diubah di sini (diatur di halaman Pengaturan Gaji).
         * Hanya bonus kinerja dan THR yang diinput tiap bulan.
         */
        $component = PayrollComponent::firstOrNew(['employee_id' => $employee->id]);

        if (!$component->exists) {
            $settings = AttendanceSetting::current();
            $component->fill([
                'base_salary'    => 0,
                'meal_rate'      => (float) ($settings->meal_rate ?? 10000),
                'transport_rate' => (float) ($settings->transport_rate ?? 10000),
                'allowance'      => 0,
                'thr_active'     => false,
            ]);
        }

        $component->bonus_kinerja  = $data['bonus_kinerja'] ?? 0;
        $component->thr_manual     = $data['thr_manual'] ?? 0;
        $component->effective_date = now()->format('Y-m-d');
        $component->save();

        return back()->with('success', "Komponen gaji {$employee->full_name} berhasil disimpan.");
    }

    /* ============================================================
     * PUBLISH SEMUA PAYROLL
     * ============================================================ */
    public function publishAll(Request $request): RedirectResponse
    {
        $this->authorizeOwner();

        $periode  = $this->resolvePeriode($request);
        $settings = AttendanceSetting::current();

        $payrollPeriod = PayrollPeriod::firstOrCreate(
            [
                'period_year'  => $periode->year,
                'period_month' => $periode->month,
            ],
            ['hari_efektif' => 0]
        );

        $hariEfektif = (int) $payrollPeriod->hari_efektif;

        if ($hariEfektif <= 0) {
            return back()->withErrors([
                'hari_efektif' => "Hari efektif periode {$periode->translatedFormat('F Y')} belum diisi. Silakan isi terlebih dahulu.",
            ]);
        }

        $employees = Employee::with('payrollComponent')->get();

        foreach ($employees as $employee) {

            if (!$employee->payrollComponent) {
                continue;
            }

            /* ---------- KARYAWAN TETAP ---------- */
            if ($employee->employee_type === 'tetap') {

                $data      = $this->attachTetapSummary($employee, $periode, $settings, $payrollPeriod);
                $component = $data->payrollComponent;

                $gajiPokok         = (float) ($component->base_salary ?? 0);
                $hariHadir         = (int) $data->hari_hadir;
                $gajiPokokDiterima = $hariEfektif > 0 ? ($gajiPokok / $hariEfektif) * $hariHadir : 0;
                $uangMakan         = (float) $settings->meal_rate * $hariHadir;
                $uangBensin        = (float) $settings->transport_rate * $hariHadir;
                $bonusKerajinan    = (float) ($component->bonus_kerajinan ?? 0);
                $bonusKinerja      = (float) ($component->bonus_kinerja ?? 0);
                $thr               = (float) $data->thr;
                $potongan          = (float) $data->potongan_telat;

                $pendapatan = $gajiPokokDiterima + $uangMakan + $uangBensin
                            + $bonusKerajinan + $bonusKinerja + $thr;

                Payslip::updateOrCreate(
                    [
                        'employee_id'  => $employee->id,
                        'period_month' => $periode->month,
                        'period_year'  => $periode->year,
                    ],
                    [
                        'hari_efektif'     => $hariEfektif,
                        'hari_hadir'       => $hariHadir,
                        'gaji_pokok'       => $gajiPokokDiterima,
                        'uang_makan'       => $uangMakan,
                        'uang_bensin'      => $uangBensin,
                        'bonus_kerajinan'  => $bonusKerajinan,
                        'bonus_kinerja'    => $bonusKinerja,
                        'potongan_telat'   => $potongan,
                        'thr'              => $thr,
                        'total_pendapatan' => $pendapatan,
                        'total_potongan'   => $potongan,
                        'total_diterima'   => $pendapatan - $potongan,
                        'published_at'     => now(),
                    ]
                );

                $component->update(['thr_manual' => 0]);
            }

            /* ---------- PART TIME (tidak berubah) ---------- */
            else {

                $component   = $employee->payrollComponent;
                $feeMengajar = (float) ($component->base_salary ?? 0);
                $uangMakan   = (float) ($component->meal_rate ?? 0);
                $uangBensin  = (float) ($component->transport_rate ?? 0);
                $pendapatan  = $feeMengajar + $uangMakan + $uangBensin;

                Payslip::updateOrCreate(
                    [
                        'employee_id'  => $employee->id,
                        'period_month' => $periode->month,
                        'period_year'  => $periode->year,
                    ],
                    [
                        'hari_efektif'     => 0,
                        'hari_hadir'       => 0,
                        'gaji_pokok'       => $feeMengajar,
                        'uang_makan'       => $uangMakan,
                        'uang_bensin'      => $uangBensin,
                        'bonus_kerajinan'  => 0,
                        'bonus_kinerja'    => 0,
                        'potongan_telat'   => 0,
                        'thr'              => 0,
                        'total_pendapatan' => $pendapatan,
                        'total_potongan'   => 0,
                        'total_diterima'   => $pendapatan,
                        'published_at'     => now(),
                    ]
                );
            }
        }

        return redirect()
            ->route('payroll.index', ['periode' => $periode->format('Y-m')])
            ->with('success', "Slip gaji periode {$periode->translatedFormat('F Y')} berhasil diterbitkan untuk semua karyawan.");
    }

    /* ============================================================
     * RIWAYAT
     * ============================================================ */
    public function history(Request $request): View
    {
        $this->authorizeOwner();

        $periods = Payslip::selectRaw('period_year, period_month')
            ->distinct()
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get();

        $selectedYear  = (int) $request->query('year', optional($periods->first())->period_year);
        $selectedMonth = (int) $request->query('month', optional($periods->first())->period_month);

        $payslips = $this->getPayslips($selectedYear, $selectedMonth);

        $periodeLabel = ($selectedYear && $selectedMonth)
            ? Carbon::createFromDate($selectedYear, $selectedMonth, 1)->translatedFormat('F Y')
            : null;

        return view('owner.payroll-history', [
            'periods'       => $periods,
            'payslips'      => $payslips,
            'selectedYear'  => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'periodeLabel'  => $periodeLabel,
        ]);
    }

    /**
     * Unduh rekap payroll satu periode dalam PDF (bukti laporan ke pusat).
     */
    public function historyPdf(Request $request)
    {
        $this->authorizeOwner();

        $data = $request->validate([
            'year'  => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $payslips = $this->getPayslips((int) $data['year'], (int) $data['month']);

        if ($payslips->isEmpty()) {
            return back()->withErrors(['pdf' => 'Tidak ada slip gaji pada periode ini.']);
        }

        $periodeLabel = Carbon::createFromDate($data['year'], $data['month'], 1)
            ->translatedFormat('F Y');

        $pdf = Pdf::loadView('owner.payroll-history-pdf', [
            'payslips'     => $payslips,
            'periodeLabel' => $periodeLabel,
            'dicetakOleh'  => auth()->user()->name ?? 'Owner',
            'dicetakPada'  => now()->translatedFormat('d F Y, H:i'),
        ])->setPaper('a4', 'landscape');

        $filename = sprintf('payroll-%04d-%02d.pdf', $data['year'], $data['month']);

        return $pdf->download($filename);
    }

    /* ============================================================
     * HELPER
     * ============================================================ */
    private function authorizeOwner(): void
    {
        abort_unless(auth()->user()->role === 'owner', 403);
    }

    private function getPayslips(int $year, int $month)
    {
        if (!$year || !$month) {
            return collect();
        }

        return Payslip::with('employee')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->orderBy('employee_id')
            ->get();
    }

    private function resolvePeriode(Request $request): Carbon
    {
        $periode = $request->query('periode') ?? $request->input('periode');

        if ($periode) {
            try {
                return Carbon::createFromFormat('Y-m', $periode)->startOfMonth();
            } catch (\Exception $e) {
                //
            }
        }

        return Carbon::now()->startOfMonth();
    }

    /* ============================================================
     * SUMMARY KARYAWAN TETAP
     * ============================================================ */
    private function attachTetapSummary(
        Employee $employee,
        Carbon $periode,
        AttendanceSetting $settings,
        PayrollPeriod $payrollPeriod
    ): Employee {

        $hariEfektif = (int) $payrollPeriod->hari_efektif;

        $start = $periode->copy()->startOfMonth()->toDateString();
        $end   = $periode->copy()->endOfMonth()->toDateString();

        $hariHadir = Attendance::where('employee_id', $employee->id)
            ->whereBetween('tanggal', [$start, $end])
            ->whereIn('status', ['tepat_waktu', 'terlambat'])
            ->whereNotNull('check_out')
            ->distinct('tanggal')
            ->count('tanggal');

        $totalTelatMenit = Attendance::where('employee_id', $employee->id)
            ->whereBetween('tanggal', [$start, $end])
            ->where('status', 'terlambat')
            ->whereNotNull('check_out')
            ->sum('late_minutes');

        $jumlahTelat = Attendance::where('employee_id', $employee->id)
            ->whereBetween('tanggal', [$start, $end])
            ->where('status', 'terlambat')
            ->whereNotNull('check_out')
            ->count();

        $potonganTelat = $totalTelatMenit * (float) $settings->late_deduction_per_minute;

        /* THR aktif mulai tahun ke-N (nominal diinput manual) */
        $thrAktif = false;

        if ($employee->join_date) {
            $tanggalAktifThr = $employee->join_date
                ->copy()
                ->addYears(max(1, ((int) $settings->thr_start_year) - 1));

            $thrAktif = $periode->copy()->endOfMonth()->gte($tanggalAktifThr);
        }

        $component = $employee->payrollComponent;
        $gajiPokok = (float) ($component->base_salary ?? 0);

        $thr = $thrAktif ? (float) ($component->thr_manual ?? 0) : 0;

        $employee->hari_efektif       = $hariEfektif;
        $employee->hari_hadir         = $hariHadir;
        $employee->jumlah_telat       = $jumlahTelat;
        $employee->total_telat_menit  = $totalTelatMenit;
        $employee->potongan_telat     = $potonganTelat;
        $employee->thr_aktif          = $thrAktif;
        $employee->thr                = $thr;
        $employee->gaji_locked        = (bool) ($component->gaji_locked ?? false);

        $employee->uang_makan  = (float) $settings->meal_rate * $hariHadir;
        $employee->uang_bensin = (float) $settings->transport_rate * $hariHadir;

        $employee->gaji_pokok_diterima = $hariEfektif > 0
            ? ($gajiPokok / $hariEfektif) * $hariHadir
            : 0;

       // Bonus diatur di Pengaturan Gaji (terkunci per karyawan)
        $employee->bonus_kerajinan = (float) ($component->bonus_kerajinan ?? 0);
        $employee->bonus_kinerja   = (float) ($component->bonus_kinerja ?? 0);

        $employee->total_diterima =
            $employee->gaji_pokok_diterima
            + $employee->uang_makan
            + $employee->uang_bensin
            + $employee->bonus_kerajinan
            + $employee->bonus_kinerja
            + $employee->thr
            - $employee->potongan_telat;

        return $employee;
    }

    /* ============================================================
     * SUMMARY PART TIME (tidak diubah)
     * ============================================================ */
    private function attachPartTimeSummary(Employee $employee, Carbon $periode): Employee
    {
        $employee->hari_hadir        = 0;
        $employee->jumlah_telat      = 0;
        $employee->total_telat_menit = 0;
        $employee->potongan_telat    = 0;

        $component = $employee->payrollComponent;

        $employee->uang_makan  = (float) ($component->meal_rate ?? 0);
        $employee->uang_bensin = (float) ($component->transport_rate ?? 0);
        $employee->makan_bensin = $employee->uang_makan + $employee->uang_bensin;

        return $employee;
    }
}