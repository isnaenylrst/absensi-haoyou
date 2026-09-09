<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PayrollController extends Controller
{
    /**
     * ============================================================
     * HALAMAN PAYROLL
     * ============================================================
     */
    public function index(Request $request): View
    {
        abort_unless(
            auth()->user()->role === 'owner',
            403
        );

        $periode = $this->resolvePeriode($request);

        $settings = AttendanceSetting::current();

        /*
        |--------------------------------------------------------------------------
        | PAYROLL PERIOD
        |--------------------------------------------------------------------------
        | Setiap bulan memiliki hari efektif sendiri.
        */
        $payrollPeriod = PayrollPeriod::firstOrCreate(
            [
                'period_year' => $periode->year,
                'period_month' => $periode->month,
            ],
            [
                'hari_efektif' => 0,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | KARYAWAN TETAP
        |--------------------------------------------------------------------------
        */
        $tetapEmployees = Employee::where(
                'employee_type',
                'tetap'
            )
            ->with('payrollComponent')
            ->get()
            ->map(function ($employee) use (
                $periode,
                $settings,
                $payrollPeriod
            ) {
                return $this->attachTetapSummary(
                    $employee,
                    $periode,
                    $settings,
                    $payrollPeriod
                );
            });

        /*
        |--------------------------------------------------------------------------
        | PART TIME
        |--------------------------------------------------------------------------
        | SISTEM PART TIME TETAP SEPERTI SEBELUMNYA.
        |--------------------------------------------------------------------------
        */
        $partTimeEmployees = Employee::where(
                'employee_type',
                'part_time'
            )
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

            'periodeLabel' =>
                $periode->translatedFormat('F Y'),

            'periodeValue' =>
                $periode->format('Y-m'),

            'isCurrentPeriode' =>
                $periode->isSameMonth(Carbon::now()),

            'rateMakan' =>
                (float) $settings->meal_rate,

            'rateBensin' =>
                (float) $settings->transport_rate,

            /*
            |--------------------------------------------------------------------------
            | DATA HARI EFEKTIF UNTUK BLADE
            |--------------------------------------------------------------------------
            */
            'payrollPeriod' =>
                $payrollPeriod,

            'hariEfektif' =>
                (int) $payrollPeriod->hari_efektif,
        ]);
    }


    /**
     * ============================================================
     * UPDATE HARI EFEKTIF
     * ============================================================
     *
     * Owner memasukkan hari efektif untuk bulan yang sedang dipilih.
     */
    public function updatePeriod(
        Request $request
    ): RedirectResponse {

        abort_unless(
            auth()->user()->role === 'owner',
            403
        );

        $data = $request->validate([
            'periode' => [
                'required',
                'date_format:Y-m',
            ],

            'hari_efektif' => [
                'required',
                'integer',
                'min:0',
                'max:31',
            ],
        ]);

        $periode = Carbon::createFromFormat(
            'Y-m',
            $data['periode']
        )->startOfMonth();

        PayrollPeriod::updateOrCreate(
            [
                'period_year' => $periode->year,
                'period_month' => $periode->month,
            ],
            [
                'hari_efektif' =>
                    $data['hari_efektif'],
            ]
        );

        return redirect()
            ->route(
                'payroll.index',
                [
                    'periode' =>
                        $periode->format('Y-m'),
                ]
            )
            ->with(
                'success',
                "Hari efektif periode {$periode->translatedFormat('F Y')} berhasil disimpan."
            );
    }


    /**
     * ============================================================
     * UPDATE KOMPONEN PAYROLL
     * ============================================================
     */
    public function updateComponent(
        Request $request,
        Employee $employee
    ): RedirectResponse {

        $data = $request->validate([
            'base_salary' => [
                'required',
                'numeric',
                'min:0',
            ],

            'bonus_kerajinan' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'bonus_kinerja' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'thr_manual' => [
            'nullable',
            'numeric',
            'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | KHUSUS PART TIME
            |--------------------------------------------------------------------------
            */
            'meal_rate' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'transport_rate' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | PART TIME
        |--------------------------------------------------------------------------
        */
        if ($employee->employee_type === 'part_time') {

            PayrollComponent::updateOrCreate(
                [
                    'employee_id' =>
                        $employee->id,
                ],
                [
                    'base_salary' =>
                        $data['base_salary'],

                    'meal_rate' =>
                        $data['meal_rate'] ?? 0,

                    'transport_rate' =>
                        $data['transport_rate'] ?? 0,

                    'allowance' => 0,

                    'bonus_kerajinan' => 0,

                    'bonus_kinerja' => 0,

                    'thr_active' => false,

                    'effective_date' =>
                        now()->format('Y-m-d'),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | KARYAWAN TETAP
        |--------------------------------------------------------------------------
        */
        else {

            $settings =
                AttendanceSetting::current();

            PayrollComponent::updateOrCreate(
                [
                    'employee_id' =>
                        $employee->id,
                ],
                [
                    'base_salary' =>
                        $data['base_salary'],

                    /*
                    |--------------------------------------------------------------------------
                    | RATE MAKAN & BENSIN KARYAWAN TETAP
                    |--------------------------------------------------------------------------
                    */
                    'meal_rate' =>
                        (float) ($settings->meal_rate ?? 10000),

                    'transport_rate' =>
                        (float) ($settings->transport_rate ?? 10000),

                    'bonus_kerajinan' =>
                        $data['bonus_kerajinan'] ?? 0,

                    'bonus_kinerja' =>
                        $data['bonus_kinerja'] ?? 0,
                    
                    'thr_manual' =>
                    $data['thr_manual'] ?? 0,

                    'allowance' => 0,

                    'thr_active' => false,

                    'effective_date' =>
                        now()->format('Y-m-d'),
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
     * PUBLISH SEMUA PAYROLL
     * ============================================================
     */
    public function publishAll(
        Request $request
    ): RedirectResponse {

        abort_unless(
            auth()->user()->role === 'owner',
            403
        );

        $periode = $this->resolvePeriode($request);

        $settings =
            AttendanceSetting::current();

        /*
        |--------------------------------------------------------------------------
        | AMBIL HARI EFEKTIF BULAN TERSEBUT
        |--------------------------------------------------------------------------
        */
        $payrollPeriod =
            PayrollPeriod::firstOrCreate(
                [
                    'period_year' =>
                        $periode->year,

                    'period_month' =>
                        $periode->month,
                ],
                [
                    'hari_efektif' => 0,
                ]
            );

        $hariEfektif =
            (int) $payrollPeriod->hari_efektif;

        /*
        |--------------------------------------------------------------------------
        | CEK HARI EFEKTIF
        |--------------------------------------------------------------------------
        */
        if ($hariEfektif <= 0) {

            return back()->withErrors([
                'hari_efektif' =>
                    "Hari efektif periode {$periode->translatedFormat('F Y')} belum diisi. Silakan isi terlebih dahulu.",
            ]);
        }


        $employees =
            Employee::with('payrollComponent')
                ->get();


        foreach ($employees as $employee) {

            if (!$employee->payrollComponent) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | KARYAWAN TETAP
            |--------------------------------------------------------------------------
            */
            if ($employee->employee_type === 'tetap') {

                $data =
                    $this->attachTetapSummary(
                        $employee,
                        $periode,
                        $settings,
                        $payrollPeriod
                    );

                $component =
                    $data->payrollComponent;


                $gajiPokok =
                    (float) (
                        $component->base_salary ?? 0
                    );


                $hariHadir =
                    (int) $data->hari_hadir;


                /*
                |--------------------------------------------------------------------------
                | GAJI POKOK PRORATA
                |--------------------------------------------------------------------------
                */
                $gajiPokokDiterima = 0;

                if ($hariEfektif > 0) {

                    $gajiPokokDiterima =
                        ($gajiPokok / $hariEfektif)
                        * $hariHadir;
                }


                /*
                |--------------------------------------------------------------------------
                | UANG MAKAN
                |--------------------------------------------------------------------------
                */
                $uangMakan =
                    (float) $settings->meal_rate
                    * $hariHadir;


                /*
                |--------------------------------------------------------------------------
                | UANG BENSIN
                |--------------------------------------------------------------------------
                */
                $uangBensin =
                    (float) $settings->transport_rate
                    * $hariHadir;


                /*
                |--------------------------------------------------------------------------
                | BONUS KERAJINAN
                |--------------------------------------------------------------------------
                */
                $bonusKerajinan =
                    (float) (
                        $component->bonus_kerajinan ?? 0
                    );


                /*
                |--------------------------------------------------------------------------
                | BONUS KINERJA
                |--------------------------------------------------------------------------
                */
                $bonusKinerja =
                    (float) (
                        $component->bonus_kinerja ?? 0
                    );


                /*
                |--------------------------------------------------------------------------
                | THR
                |--------------------------------------------------------------------------
                */
                $thr =
                    (float) $data->thr;


                /*
                |--------------------------------------------------------------------------
                | TOTAL PENDAPATAN
                |--------------------------------------------------------------------------
                */
                $pendapatan =
                    $gajiPokokDiterima
                    + $uangMakan
                    + $uangBensin
                    + $bonusKerajinan
                    + $bonusKinerja
                    + $thr;


                /*
                |--------------------------------------------------------------------------
                | POTONGAN
                |--------------------------------------------------------------------------
                */
                $potongan =
                    (float) $data->potongan_telat;


                /*
                |--------------------------------------------------------------------------
                | TOTAL DITERIMA
                |--------------------------------------------------------------------------
                */
                $totalDiterima =
                    $pendapatan - $potongan;


                Payslip::updateOrCreate(
                    [
                        'employee_id' =>
                            $employee->id,

                        'period_month' =>
                            $periode->month,

                        'period_year' =>
                            $periode->year,
                    ],
                    [
                        'hari_efektif' =>
                            $hariEfektif,

                        'hari_hadir' =>
                            $hariHadir,

                        'gaji_pokok' =>
                            $gajiPokokDiterima,

                        'uang_makan' =>
                            $uangMakan,

                        'uang_bensin' =>
                            $uangBensin,

                        'bonus_kerajinan' =>
                            $bonusKerajinan,

                        'bonus_kinerja' =>
                            $bonusKinerja,

                        'potongan_telat' =>
                            $potongan,

                        'thr' =>
                            $thr,

                        'total_pendapatan' =>
                            $pendapatan,

                        'total_potongan' =>
                            $potongan,

                        'total_diterima' =>
                            $totalDiterima,

                        'published_at' =>
                            now(),
                    ]
                );
                
                $component->update(['thr_manual' => 0]);
            }


            /*
            |--------------------------------------------------------------------------
            | PART TIME
            |--------------------------------------------------------------------------
            | TIDAK DIUBAH.
            |--------------------------------------------------------------------------
            */
            else {

                $component =
                    $employee->payrollComponent;


                $feeMengajar =
                    (float) (
                        $component->base_salary ?? 0
                    );


                $uangMakan =
                    (float) (
                        $component->meal_rate ?? 0
                    );


                $uangBensin =
                    (float) (
                        $component->transport_rate ?? 0
                    );


                $pendapatan =
                    $feeMengajar
                    + $uangMakan
                    + $uangBensin;


                Payslip::updateOrCreate(
                    [
                        'employee_id' =>
                            $employee->id,

                        'period_month' =>
                            $periode->month,

                        'period_year' =>
                            $periode->year,
                    ],
                    [
                        'hari_efektif' => 0,

                        'hari_hadir' => 0,

                        'gaji_pokok' =>
                            $feeMengajar,

                        'uang_makan' =>
                            $uangMakan,

                        'uang_bensin' =>
                            $uangBensin,

                        'bonus_kerajinan' => 0,

                        'bonus_kinerja' => 0,

                        'potongan_telat' => 0,

                        'thr' => 0,

                        'total_pendapatan' =>
                            $pendapatan,

                        'total_potongan' => 0,

                        'total_diterima' =>
                            $pendapatan,

                        'published_at' =>
                            now(),
                    ]
                );
            }
        }


        return redirect()
            ->route(
                'payroll.index',
                [
                    'periode' =>
                        $periode->format('Y-m'),
                ]
            )
            ->with(
                'success',
                "Slip gaji periode {$periode->translatedFormat('F Y')} berhasil diterbitkan untuk semua karyawan."
            );
    }


    /**
     * ============================================================
     * HISTORY
     * ============================================================
     */
    public function history(
        Request $request
    ): View {

        abort_unless(
            auth()->user()->role === 'owner',
            403
        );

        $periods =
            Payslip::selectRaw(
                'period_year, period_month'
            )
            ->distinct()
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get();


        $selectedYear =
            (int) $request->query(
                'year',
                optional($periods->first())
                    ->period_year
            );


        $selectedMonth =
            (int) $request->query(
                'month',
                optional($periods->first())
                    ->period_month
            );


        $payslips =
            Payslip::with('employee')
                ->where(
                    'period_year',
                    $selectedYear
                )
                ->where(
                    'period_month',
                    $selectedMonth
                )
                ->orderBy('employee_id')
                ->get();


        $periodeLabel =
            (
                $selectedYear
                && $selectedMonth
            )
            ? Carbon::createFromDate(
                $selectedYear,
                $selectedMonth,
                1
            )->translatedFormat('F Y')
            : null;


        return view(
            'owner.payroll-history',
            [
                'periods' =>
                    $periods,

                'payslips' =>
                    $payslips,

                'selectedYear' =>
                    $selectedYear,

                'selectedMonth' =>
                    $selectedMonth,

                'periodeLabel' =>
                    $periodeLabel,
            ]
        );
    }


    /**
     * ============================================================
     * RESOLVE PERIODE
     * ============================================================
     */
    private function resolvePeriode(
        Request $request
    ): Carbon {

        $periode =
            $request->query('periode');


        if ($periode) {

            try {

                return Carbon::createFromFormat(
                    'Y-m',
                    $periode
                )->startOfMonth();

            } catch (\Exception $e) {
                //
            }
        }


        return Carbon::now()
            ->startOfMonth();
    }


    /**
     * ============================================================
     * SUMMARY KARYAWAN TETAP
     * ============================================================
     */
    private function attachTetapSummary(
        Employee $employee,
        Carbon $periode,
        AttendanceSetting $settings,
        PayrollPeriod $payrollPeriod
    ): Employee {

        /*
        |--------------------------------------------------------------------------
        | HARI EFEKTIF
        |--------------------------------------------------------------------------
        | SEKARANG DIAMBIL DARI INPUT OWNER.
        |
        | Tidak lagi menghitung Senin-Sabtu otomatis.
        |--------------------------------------------------------------------------
        */
        $hariEfektif =
            (int) $payrollPeriod->hari_efektif;


        /*
        |--------------------------------------------------------------------------
        | HARI HADIR DARI ABSENSI
        |--------------------------------------------------------------------------
        */
        $hariHadir =
            Attendance::where(
                'employee_id',
                $employee->id
            )
            ->whereBetween(
                'tanggal',
                [
                    $periode->copy()
                        ->startOfMonth()
                        ->toDateString(),

                    $periode->copy()
                        ->endOfMonth()
                        ->toDateString(),
                ]
            )
            ->whereIn(
                'status',
                [
                    'tepat_waktu',
                    'terlambat',
                ]
            )
            ->whereNotNull('check_out') // ⬅️
            ->distinct('tanggal')
            ->count('tanggal');


        /*
        |--------------------------------------------------------------------------
        | TOTAL MENIT TELAT
        |--------------------------------------------------------------------------
        */
        $totalTelatMenit =
            Attendance::where(
                'employee_id',
                $employee->id
            )
            ->whereBetween(
                'tanggal',
                [
                    $periode->copy()
                        ->startOfMonth()
                        ->toDateString(),

                    $periode->copy()
                        ->endOfMonth()
                        ->toDateString(),
                ]
            )
            ->where(
                'status',
                'terlambat'
            )
            ->whereNotNull('check_out')
            ->sum('late_minutes');
            


        /*
        |--------------------------------------------------------------------------
        | JUMLAH KETERLAMBATAN
        |--------------------------------------------------------------------------
        */
        $jumlahTelat =
            Attendance::where(
                'employee_id',
                $employee->id
            )
            ->whereBetween(
                'tanggal',
                [
                    $periode->copy()
                        ->startOfMonth()
                        ->toDateString(),

                    $periode->copy()
                        ->endOfMonth()
                        ->toDateString(),
                ]
            )
            ->where(
                'status',
                'terlambat'
            )
            ->whereNotNull('check_out')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | POTONGAN TELAT
        |--------------------------------------------------------------------------
        */
        $potonganTelat =
            $totalTelatMenit
            * (float) $settings->late_deduction_per_minute;


        /*
        |--------------------------------------------------------------------------
        | THR
        |--------------------------------------------------------------------------
        */
        $thrAktif = false;

        if ($employee->join_date) {

            $tanggalAktifThr =
                $employee->join_date
                    ->copy()
                    ->addYears(
                        max(
                            1,
                            ((int) $settings->thr_start_year) - 1
                        )
                    );

            $thrAktif =
                $periode->copy()
                    ->endOfMonth()
                    ->gte($tanggalAktifThr);
        }


        $component =
            $employee->payrollComponent;


        $gajiPokok =
            (float) (
                $component->base_salary ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | THR = 1X GAJI POKOK
        |--------------------------------------------------------------------------
        */
        $thr =  
        $thrAktif
        ? (float) ($component->thr_manual ?? 0)
        : 0;

        /*
        |--------------------------------------------------------------------------
        | SIMPAN KE OBJECT UNTUK BLADE
        |--------------------------------------------------------------------------
        */
        $employee->hari_efektif =
            $hariEfektif;

        $employee->hari_hadir =
            $hariHadir;

        $employee->jumlah_telat =
            $jumlahTelat;

        $employee->total_telat_menit =
            $totalTelatMenit;

        $employee->potongan_telat =
            $potonganTelat;

        $employee->thr_aktif =
            $thrAktif;

        $employee->thr =
            $thr;


        /*
        |--------------------------------------------------------------------------
        | UANG MAKAN
        |--------------------------------------------------------------------------
        */
        $employee->uang_makan =
            (float) $settings->meal_rate
            * $hariHadir;


        /*
        |--------------------------------------------------------------------------
        | UANG BENSIN
        |--------------------------------------------------------------------------
        */
        $employee->uang_bensin =
            (float) $settings->transport_rate
            * $hariHadir;


        /*
        |--------------------------------------------------------------------------
        | GAJI POKOK PRORATA
        |--------------------------------------------------------------------------
        */
        $employee->gaji_pokok_diterima =
            $hariEfektif > 0
            ? ($gajiPokok / $hariEfektif)
                * $hariHadir
            : 0;


        /*
        |--------------------------------------------------------------------------
        | BONUS
        |--------------------------------------------------------------------------
        */
        $employee->bonus_kerajinan =
            (float) (
                $component->bonus_kerajinan ?? 0
            );

        $employee->bonus_kinerja =
            (float) (
                $component->bonus_kinerja ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | TOTAL DITERIMA
        |--------------------------------------------------------------------------
        */
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


    /**
     * ============================================================
     * SUMMARY PART TIME
     * ============================================================
     *
     * JANGAN DIUBAH.
     */
    private function attachPartTimeSummary(
        Employee $employee,
        Carbon $periode
    ): Employee {

        $employee->hari_hadir = 0;

        $employee->jumlah_telat = 0;

        $employee->total_telat_menit = 0;

        $employee->potongan_telat = 0;

        $component =
            $employee->payrollComponent;


        $employee->uang_makan =
            (float) (
                $component->meal_rate ?? 0
            );


        $employee->uang_bensin =
            (float) (
                $component->transport_rate ?? 0
            );


        $employee->makan_bensin =
            $employee->uang_makan
            + $employee->uang_bensin;


        return $employee;
    }
}