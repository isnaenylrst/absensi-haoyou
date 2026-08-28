<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePayrollComponentRequest;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\Payslip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PayrollController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | RATE UANG MAKAN & BENSIN
    |--------------------------------------------------------------------------
    | Rp10.000 makan + Rp10.000 bensin
    | Total = Rp20.000 per hari hadir
    */

    private const RATE_MAKAN = 10000;
    private const RATE_BENSIN = 10000;
    private const RATE_MAKAN_BENSIN = 20000;

    /**
     * ============================================================
     * HALAMAN PAYROLL OWNER
     * ============================================================
     */
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->role === 'owner', 403);

        $periode = $this->resolvePeriode($request);

        // Ambil pengaturan keterlambatan dari database
        $settings = AttendanceSetting::current();

        /*
        |--------------------------------------------------------------------------
        | KARYAWAN TETAP
        |--------------------------------------------------------------------------
        */
        $tetapEmployees = Employee::where('employee_type', 'tetap')
            ->with('payrollComponent')
            ->get()
            ->map(function ($employee) use ($periode, $settings) {
                return $this->attachTetapSummary(
                    $employee,
                    $periode,
                    $settings
                );
            });

        /*
        |--------------------------------------------------------------------------
        | KARYAWAN PART TIME
        |--------------------------------------------------------------------------
        */
        $partTimeEmployees = Employee::where('employee_type', 'part_time')
            ->with('payrollComponent')
            ->get()
            ->map(function ($employee) use ($periode) {
                return $this->attachPartTimeSummary(
                    $employee,
                    $periode
                );
            });

        return view('owner.payroll', [
            'tetapEmployees' => $tetapEmployees,
            'partTimeEmployees' => $partTimeEmployees,

            'periodeLabel' => $periode->translatedFormat('F Y'),
            'periodeValue' => $periode->format('Y-m'),

            'isCurrentPeriode' => $periode->isSameMonth(Carbon::now()),

            /*
            |--------------------------------------------------------------------------
            | RATE
            |--------------------------------------------------------------------------
            */
            'rateMakan' => self::RATE_MAKAN,
            'rateBensin' => self::RATE_BENSIN,
            'rateMakanBensin' => self::RATE_MAKAN_BENSIN,
        ]);
    }

    /**
     * ============================================================
     * SIMPAN KOMPONEN GAJI
     * ============================================================
     *
     * Owner dapat menginput:
     *
     * Karyawan Tetap:
     * - Gaji Pokok
     * - Bonus
     * - THR aktif
     *
     * Part Time:
     * - Fee Mengajar
     * - Bonus
     *
     * Uang makan dan bensin tidak perlu dihitung manual
     * karena sistem otomatis berdasarkan jumlah hari hadir.
     */
    public function updateComponent(
        UpdatePayrollComponentRequest $request,
        Employee $employee
    ): RedirectResponse {

        PayrollComponent::updateOrCreate(
            [
                'employee_id' => $employee->id,
            ],
            [
                /*
                |--------------------------------------------------------------------------
                | TETAP
                |--------------------------------------------------------------------------
                | base_salary = gaji pokok bulanan
                |
                | PART TIME
                |
                | base_salary = fee mengajar
                */
                'base_salary' => $request->base_salary,

                /*
                |--------------------------------------------------------------------------
                | UANG MAKAN
                |--------------------------------------------------------------------------
                | Sistem menetapkan Rp10.000/hari.
                |
                | Jadi sebenarnya owner tidak perlu mengetik
                | angka ini lagi.
                */
                'meal_rate' => self::RATE_MAKAN,

                /*
                |--------------------------------------------------------------------------
                | UANG BENSIN
                |--------------------------------------------------------------------------
                */
                'transport_rate' => self::RATE_BENSIN,

                /*
                |--------------------------------------------------------------------------
                | BONUS
                |--------------------------------------------------------------------------
                */
                'allowance' => $request->allowance ?? 0,

                /*
                |--------------------------------------------------------------------------
                | THR
                |--------------------------------------------------------------------------
                */
                'thr_active' => $request->boolean('thr_active'),

                'effective_date' => now()->format('Y-m-d'),
            ]
        );

        return back()->with(
            'success',
            "Komponen gaji {$employee->full_name} berhasil disimpan."
        );
    }

    /**
     * ============================================================
     * TERBITKAN SEMUA SLIP GAJI
     * ============================================================
     */
    public function publishAll(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->role === 'owner', 403);

        $periode = $this->resolvePeriode($request);

        $settings = AttendanceSetting::current();

        $employees = Employee::with('payrollComponent')->get();

        foreach ($employees as $employee) {

            /*
            |--------------------------------------------------------------------------
            | Jika belum ada komponen payroll
            |--------------------------------------------------------------------------
            */
            if (!$employee->payrollComponent) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | KARYAWAN TETAP
            |--------------------------------------------------------------------------
            */
            if ($employee->employee_type === 'tetap') {

                $data = $this->attachTetapSummary(
                    $employee,
                    $periode,
                    $settings
                );

                $component = $data->payrollComponent;

                /*
                |--------------------------------------------------------------------------
                | GAJI POKOK
                |--------------------------------------------------------------------------
                */
                $gajiPokok = (float) ($component->base_salary ?? 0);

                /*
                |--------------------------------------------------------------------------
                | UANG MAKAN
                |--------------------------------------------------------------------------
                | Rp10.000 × hari hadir
                |--------------------------------------------------------------------------
                */
                $uangMakan = self::RATE_MAKAN * $data->hari_hadir;

                /*
                |--------------------------------------------------------------------------
                | UANG BENSIN
                |--------------------------------------------------------------------------
                | Rp10.000 × hari hadir
                |--------------------------------------------------------------------------
                */
                $uangBensin = self::RATE_BENSIN * $data->hari_hadir;

                /*
                |--------------------------------------------------------------------------
                | BONUS
                |--------------------------------------------------------------------------
                */
                $bonus = (float) ($component->allowance ?? 0);

                /*
                |--------------------------------------------------------------------------
                | TOTAL PENDAPATAN
                |--------------------------------------------------------------------------
                */
                $pendapatan =
                    $gajiPokok
                    + $uangMakan
                    + $uangBensin
                    + $bonus;

                /*
                |--------------------------------------------------------------------------
                | POTONGAN KETERLAMBATAN
                |--------------------------------------------------------------------------
                |
                | Potongan dihitung otomatis dari data presensi.
                | late_minutes sudah ditentukan saat presensi.
                |
                */
                $potongan = $data->potongan_telat;

                /*
                |--------------------------------------------------------------------------
                | TOTAL DITERIMA
                |--------------------------------------------------------------------------
                */
                $totalDiterima = $pendapatan - $potongan;

                $hariHadir = $data->hari_hadir;
            }

            /*
            |--------------------------------------------------------------------------
            | KARYAWAN PART TIME
            |--------------------------------------------------------------------------
            */
            else {

                $data = $this->attachPartTimeSummary(
                    $employee,
                    $periode
                );

                $component = $data->payrollComponent;

                /*
                |--------------------------------------------------------------------------
                | FEE MENGAJAR
                |--------------------------------------------------------------------------
                | Owner menginput fee mengajar.
                |
                | base_salary dipakai sebagai tempat penyimpanan
                | fee mengajar karena struktur database saat ini
                | menggunakan field base_salary.
                */
                $feeMengajar = (float) ($component->base_salary ?? 0);

                /*
                |--------------------------------------------------------------------------
                | UANG MAKAN
                |--------------------------------------------------------------------------
                */
                $uangMakan = self::RATE_MAKAN * $data->hari_hadir;

                /*
                |--------------------------------------------------------------------------
                | UANG BENSIN
                |--------------------------------------------------------------------------
                */
                $uangBensin = self::RATE_BENSIN * $data->hari_hadir;

                /*
                |--------------------------------------------------------------------------
                | BONUS
                |--------------------------------------------------------------------------
                */
                $bonus = (float) ($component->allowance ?? 0);

                /*
                |--------------------------------------------------------------------------
                | TOTAL PENDAPATAN
                |--------------------------------------------------------------------------
                */
                $pendapatan =
                    $feeMengajar
                    + $uangMakan
                    + $uangBensin
                    + $bonus;

                /*
                |--------------------------------------------------------------------------
                | PART TIME TIDAK ADA POTONGAN KETERLAMBATAN
                |--------------------------------------------------------------------------
                */
                $potongan = 0;

                $totalDiterima = $pendapatan;

                $hariHadir = $data->hari_hadir;
            }

            /*
            |--------------------------------------------------------------------------
            | SIMPAN / UPDATE PAYSLIP
            |--------------------------------------------------------------------------
            */
            Payslip::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'period_month' => $periode->month,
                    'period_year' => $periode->year,
                ],
                [
                    'hari_hadir' => $hariHadir,

                    'total_pendapatan' => $pendapatan,

                    'total_potongan' => $potongan,

                    'total_diterima' => $totalDiterima,

                    'published_at' => now(),
                ]
            );
        }

        return redirect()
            ->route('payroll.index', [
                'periode' => $periode->format('Y-m')
            ])
            ->with(
                'success',
                "Slip gaji periode {$periode->translatedFormat('F Y')} berhasil diterbitkan untuk semua karyawan."
            );
    }

    /**
     * ============================================================
     * RIWAYAT PAYROLL
     * ============================================================
     */
    public function history(Request $request): View
    {
        abort_unless(auth()->user()->role === 'owner', 403);

        /*
        |--------------------------------------------------------------------------
        | AMBIL SEMUA PERIODE YANG SUDAH DITERBITKAN
        |--------------------------------------------------------------------------
        */
        $periods = Payslip::selectRaw(
                'period_year, period_month'
            )
            ->distinct()
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | PERIODE YANG DIPILIH
        |--------------------------------------------------------------------------
        */
        $selectedYear = (int) $request->query(
            'year',
            optional($periods->first())->period_year
        );

        $selectedMonth = (int) $request->query(
            'month',
            optional($periods->first())->period_month
        );

        /*
        |--------------------------------------------------------------------------
        | PAYSLIP
        |--------------------------------------------------------------------------
        */
        $payslips = Payslip::with('employee')
            ->where('period_year', $selectedYear)
            ->where('period_month', $selectedMonth)
            ->orderBy('employee_id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | LABEL PERIODE
        |--------------------------------------------------------------------------
        */
        $periodeLabel = ($selectedYear && $selectedMonth)
            ? Carbon::createFromDate(
                $selectedYear,
                $selectedMonth,
                1
            )->translatedFormat('F Y')
            : null;

        return view('owner.payroll-history', [
            'periods' => $periods,
            'payslips' => $payslips,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'periodeLabel' => $periodeLabel,
        ]);
    }

    /**
     * ============================================================
     * RESOLVE PERIODE
     * ============================================================
     */
    private function resolvePeriode(Request $request): Carbon
    {
        $periode = $request->query('periode');

        if ($periode) {

            try {

                return Carbon::createFromFormat(
                    'Y-m',
                    $periode
                )->startOfMonth();

            } catch (\Exception $e) {

                // Jika format salah,
                // gunakan bulan berjalan.
            }
        }

        return Carbon::now()->startOfMonth();
    }

    /**
     * ============================================================
     * SUMMARY KARYAWAN TETAP
     * ============================================================
     */
    private function attachTetapSummary(
        Employee $employee,
        Carbon $periode,
        AttendanceSetting $settings
    ): Employee {

        /*
        |--------------------------------------------------------------------------
        | JUMLAH HARI HADIR
        |--------------------------------------------------------------------------
        |
        | Diambil dari tabel attendances.
        |
        | Hanya:
        | - tepat_waktu
        | - terlambat
        |
        */
        $hariHadir = Attendance::where(
                'employee_id',
                $employee->id
            )
            ->whereMonth(
                'tanggal',
                $periode->month
            )
            ->whereYear(
                'tanggal',
                $periode->year
            )
            ->whereIn(
                'status',
                [
                    'tepat_waktu',
                    'terlambat'
                ]
            )
            ->distinct('tanggal')
            ->count('tanggal');

        /*
        |--------------------------------------------------------------------------
        | TOTAL MENIT TERLAMBAT
        |--------------------------------------------------------------------------
        */
        $totalTelatMenit = Attendance::where(
                'employee_id',
                $employee->id
            )
            ->whereMonth(
                'tanggal',
                $periode->month
            )
            ->whereYear(
                'tanggal',
                $periode->year
            )
            ->where('status', 'terlambat')
            ->sum('late_minutes');

        /*
        |--------------------------------------------------------------------------
        | JUMLAH KETERLAMBATAN
        |--------------------------------------------------------------------------
        */
        $jumlahTelat = Attendance::where(
                'employee_id',
                $employee->id
            )
            ->whereMonth(
                'tanggal',
                $periode->month
            )
            ->whereYear(
                'tanggal',
                $periode->year
            )
            ->where('status', 'terlambat')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | POTONGAN
        |--------------------------------------------------------------------------
        |
        | Menggunakan nilai dari AttendanceSetting.
        |
        | Contoh:
        | 2 kali telat
        | total 30 menit
        | deduction = Rp500/menit
        |
        | potongan = 30 × 500
        |
        */
        $potonganTelat =
            $totalTelatMenit
            * $settings->late_deduction_per_minute;

        /*
        |--------------------------------------------------------------------------
        | SIMPAN DATA TAMBAHAN KE OBJECT EMPLOYEE
        |--------------------------------------------------------------------------
        */
        $employee->hari_hadir = $hariHadir;

        $employee->jumlah_telat = $jumlahTelat;

        $employee->total_telat_menit = $totalTelatMenit;

        $employee->potongan_telat = $potonganTelat;

        /*
        |--------------------------------------------------------------------------
        | UANG MAKAN
        |--------------------------------------------------------------------------
        */
        $employee->uang_makan =
            self::RATE_MAKAN
            * $hariHadir;

        /*
        |--------------------------------------------------------------------------
        | UANG BENSIN
        |--------------------------------------------------------------------------
        */
        $employee->uang_bensin =
            self::RATE_BENSIN
            * $hariHadir;

        return $employee;
    }

    /**
     * ============================================================
     * SUMMARY KARYAWAN PART TIME
     * ============================================================
     */
    private function attachPartTimeSummary(
        Employee $employee,
        Carbon $periode
    ): Employee {

        /*
        |--------------------------------------------------------------------------
        | HARI HADIR
        |--------------------------------------------------------------------------
        |
        | Dihitung berdasarkan tanggal presensi unik.
        |
        */
        $hariHadir = Attendance::where(
                'employee_id',
                $employee->id
            )
            ->whereMonth(
                'tanggal',
                $periode->month
            )
            ->whereYear(
                'tanggal',
                $periode->year
            )
            ->whereIn(
                'status',
                [
                    'tepat_waktu',
                    'terlambat'
                ]
            )
            ->distinct('tanggal')
            ->count('tanggal');

        /*
        |--------------------------------------------------------------------------
        | PART TIME TIDAK ADA POTONGAN TELAT
        |--------------------------------------------------------------------------
        */
        $employee->hari_hadir = $hariHadir;

        $employee->jumlah_telat = 0;

        $employee->total_telat_menit = 0;

        $employee->potongan_telat = 0;

        /*
        |--------------------------------------------------------------------------
        | UANG MAKAN
        |--------------------------------------------------------------------------
        */
        $employee->uang_makan =
            self::RATE_MAKAN
            * $hariHadir;

        /*
        |--------------------------------------------------------------------------
        | UANG BENSIN
        |--------------------------------------------------------------------------
        */
        $employee->uang_bensin =
            self::RATE_BENSIN
            * $hariHadir;

        /*
        |--------------------------------------------------------------------------
        | TOTAL MAKAN + BENSIN
        |--------------------------------------------------------------------------
        */
        $employee->makan_bensin =
            self::RATE_MAKAN_BENSIN
            * $hariHadir;

        return $employee;
    }
}