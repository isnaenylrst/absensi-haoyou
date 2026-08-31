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
    | RATE UANG MAKAN & BENSIN KARYAWAN TETAP
    |--------------------------------------------------------------------------
    | Rp10.000 makan + Rp10.000 bensin
    | Total = Rp20.000 per hari hadir
    |
    | CATATAN:
    | Rate ini HANYA digunakan untuk KARYAWAN TETAP.
    |
    | KARYAWAN PART TIME:
    | Uang makan dan bensin diinput MANUAL oleh Owner.
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
        | Karyawan tetap menggunakan data absensi.
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
        | Part time TIDAK menggunakan absensi.
        |
        | Hanya mengambil:
        | - Fee Mengajar
        | - Uang Makan Manual
        | - Uang Bensin Manual
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
            | RATE UNTUK KARYAWAN TETAP
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
     * KARYAWAN TETAP:
     * - Gaji Pokok
     * - Bonus
     * - THR
     *
     * Uang makan dan bensin otomatis berdasarkan absensi.
     *
     *
     * KARYAWAN PART TIME:
     * - Fee Mengajar
     * - Uang Makan Manual
     * - Uang Bensin Manual
     *
     * Part time TIDAK menggunakan absensi.
     */
    public function updateComponent(
        UpdatePayrollComponentRequest $request,
        Employee $employee
    ): RedirectResponse {


        /*
        |--------------------------------------------------------------------------
        | KARYAWAN PART TIME
        |--------------------------------------------------------------------------
        | Semua komponen berikut diinput manual oleh Owner:
        |
        | 1. Fee Mengajar
        | 2. Uang Makan
        | 3. Uang Bensin
        |
        | Tidak ada perhitungan Rp10.000.
        */
        if ($employee->employee_type === 'part_time') {

            PayrollComponent::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                ],
                [
                    /*
                    |--------------------------------------------------------------------------
                    | FEE MENGAJAR
                    |--------------------------------------------------------------------------
                    | Disimpan di base_salary karena struktur database
                    | menggunakan field tersebut.
                    */
                    'base_salary' => $request->base_salary,

                    /*
                    |--------------------------------------------------------------------------
                    | UANG MAKAN MANUAL
                    |--------------------------------------------------------------------------
                    */
                    'meal_rate' => $request->meal_rate ?? 0,

                    /*
                    |--------------------------------------------------------------------------
                    | UANG BENSIN MANUAL
                    |--------------------------------------------------------------------------
                    */
                    'transport_rate' => $request->transport_rate ?? 0,

                    /*
                    |--------------------------------------------------------------------------
                    | PART TIME TIDAK MENGGUNAKAN BONUS
                    |--------------------------------------------------------------------------
                    */
                    'allowance' => 0,

                    /*
                    |--------------------------------------------------------------------------
                    | PART TIME TIDAK MENGGUNAKAN THR
                    |--------------------------------------------------------------------------
                    */
                    'thr_active' => false,

                    'effective_date' => now()->format('Y-m-d'),
                ]
            );

        } else {

            /*
            |--------------------------------------------------------------------------
            | KARYAWAN TETAP
            |--------------------------------------------------------------------------
            | Tetap menggunakan sistem lama:
            |
            | Uang makan = Rp10.000 × hari hadir
            | Uang bensin = Rp10.000 × hari hadir
            */
            PayrollComponent::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                ],
                [
                    /*
                    |--------------------------------------------------------------------------
                    | GAJI POKOK
                    |--------------------------------------------------------------------------
                    */
                    'base_salary' => $request->base_salary,

                    /*
                    |--------------------------------------------------------------------------
                    | UANG MAKAN
                    |--------------------------------------------------------------------------
                    | Rate tetap Rp10.000/hari.
                    */
                    'meal_rate' => self::RATE_MAKAN,

                    /*
                    |--------------------------------------------------------------------------
                    | UANG BENSIN
                    |--------------------------------------------------------------------------
                    | Rate tetap Rp10.000/hari.
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
        }


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
                $uangMakan =
                    self::RATE_MAKAN
                    * $data->hari_hadir;


                /*
                |--------------------------------------------------------------------------
                | UANG BENSIN
                |--------------------------------------------------------------------------
                | Rp10.000 × hari hadir
                |--------------------------------------------------------------------------
                */
                $uangBensin =
                    self::RATE_BENSIN
                    * $data->hari_hadir;


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
                */
                $potongan = $data->potongan_telat;


                /*
                |--------------------------------------------------------------------------
                | TOTAL DITERIMA
                |--------------------------------------------------------------------------
                */
                $totalDiterima =
                    $pendapatan
                    - $potongan;


                /*
                |--------------------------------------------------------------------------
                | HARI HADIR
                |--------------------------------------------------------------------------
                */
                $hariHadir = $data->hari_hadir;
            }


            /*
            |--------------------------------------------------------------------------
            | KARYAWAN PART TIME
            |--------------------------------------------------------------------------
            |
            | PART TIME TIDAK MENGGUNAKAN:
            | - Absensi
            | - Hari hadir
            | - Perhitungan Rp10.000
            | - Potongan telat
            | - Bonus
            |
            | Owner hanya memasukkan:
            | - Fee Mengajar
            | - Uang Makan
            | - Uang Bensin
            */
            else {

                $component = $employee->payrollComponent;


                /*
                |--------------------------------------------------------------------------
                | FEE MENGAJAR
                |--------------------------------------------------------------------------
                */
                $feeMengajar =
                    (float) ($component->base_salary ?? 0);


                /*
                |--------------------------------------------------------------------------
                | UANG MAKAN MANUAL
                |--------------------------------------------------------------------------
                | Tidak dikalikan hari hadir.
                | Tidak menggunakan Rp10.000.
                */
                $uangMakan =
                    (float) ($component->meal_rate ?? 0);


                /*
                |--------------------------------------------------------------------------
                | UANG BENSIN MANUAL
                |--------------------------------------------------------------------------
                | Tidak dikalikan hari hadir.
                | Tidak menggunakan Rp10.000.
                */
                $uangBensin =
                    (float) ($component->transport_rate ?? 0);


                /*
                |--------------------------------------------------------------------------
                | PART TIME TIDAK ADA BONUS
                |--------------------------------------------------------------------------
                */
                $bonus = 0;


                /*
                |--------------------------------------------------------------------------
                | TOTAL PENDAPATAN
                |--------------------------------------------------------------------------
                */
                $pendapatan =
                    $feeMengajar
                    + $uangMakan
                    + $uangBensin;


                /*
                |--------------------------------------------------------------------------
                | PART TIME TIDAK ADA POTONGAN
                |--------------------------------------------------------------------------
                */
                $potongan = 0;


                /*
                |--------------------------------------------------------------------------
                | TOTAL DITERIMA
                |--------------------------------------------------------------------------
                */
                $totalDiterima =
                    $pendapatan;


                /*
                |--------------------------------------------------------------------------
                | PART TIME TIDAK ADA ABSENSI
                |--------------------------------------------------------------------------
                |
                | Disimpan 0 karena memang Part Time tidak menggunakan
                | data kehadiran untuk payroll.
                */
                $hariHadir = 0;
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
        $periodeLabel =
            ($selectedYear && $selectedMonth)
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
     *
     * Karyawan tetap menggunakan absensi.
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
        | Diambil dari tabel attendances.
        |
        | Hanya:
        | - tepat_waktu
        | - terlambat
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
        */
        $potonganTelat =
            $totalTelatMenit
            * $settings->late_deduction_per_minute;


        /*
        |--------------------------------------------------------------------------
        | SIMPAN DATA TAMBAHAN KE OBJECT EMPLOYEE
        |--------------------------------------------------------------------------
        */
        $employee->hari_hadir =
            $hariHadir;


        $employee->jumlah_telat =
            $jumlahTelat;


        $employee->total_telat_menit =
            $totalTelatMenit;


        $employee->potongan_telat =
            $potonganTelat;


        /*
        |--------------------------------------------------------------------------
        | UANG MAKAN
        |--------------------------------------------------------------------------
        | Rp10.000 × hari hadir
        |--------------------------------------------------------------------------
        */
        $employee->uang_makan =
            self::RATE_MAKAN
            * $hariHadir;


        /*
        |--------------------------------------------------------------------------
        | UANG BENSIN
        |--------------------------------------------------------------------------
        | Rp10.000 × hari hadir
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


    /**
     * ============================================================
     * SUMMARY KARYAWAN PART TIME
     * ============================================================
     *
     * PART TIME TIDAK MENGGUNAKAN ABSENSI.
     *
     * Komponen:
     * - Fee Mengajar
     * - Uang Makan Manual
     * - Uang Bensin Manual
     *
     * Tidak ada:
     * - Hari hadir
     * - Potongan telat
     * - Rate Rp10.000
     */
    private function attachPartTimeSummary(
        Employee $employee,
        Carbon $periode
    ): Employee {


        /*
        |--------------------------------------------------------------------------
        | PART TIME TIDAK MENGGUNAKAN ABSENSI
        |--------------------------------------------------------------------------
        */
        $employee->hari_hadir = 0;


        /*
        |--------------------------------------------------------------------------
        | TIDAK ADA KETERLAMBATAN
        |--------------------------------------------------------------------------
        */
        $employee->jumlah_telat = 0;

        $employee->total_telat_menit = 0;

        $employee->potongan_telat = 0;


        /*
        |--------------------------------------------------------------------------
        | AMBIL KOMPONEN PAYROLL
        |--------------------------------------------------------------------------
        */
        $component =
            $employee->payrollComponent;


        /*
        |--------------------------------------------------------------------------
        | UANG MAKAN MANUAL
        |--------------------------------------------------------------------------
        | Nilainya berasal dari input Owner.
        |
        | Tidak dikalikan hari hadir.
        | Tidak menggunakan rate Rp10.000.
        |--------------------------------------------------------------------------
        */
        $employee->uang_makan =
            (float) ($component->meal_rate ?? 0);


        /*
        |--------------------------------------------------------------------------
        | UANG BENSIN MANUAL
        |--------------------------------------------------------------------------
        | Nilainya berasal dari input Owner.
        |
        | Tidak dikalikan hari hadir.
        | Tidak menggunakan rate Rp10.000.
        |--------------------------------------------------------------------------
        */
        $employee->uang_bensin =
            (float) ($component->transport_rate ?? 0);


        /*
        |--------------------------------------------------------------------------
        | TOTAL MAKAN + BENSIN
        |--------------------------------------------------------------------------
        */
        $employee->makan_bensin =
            $employee->uang_makan
            + $employee->uang_bensin;


        return $employee;
    }
}