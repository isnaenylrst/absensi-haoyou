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
    private const RATE_MAKAN_BENSIN_PART_TIME = 20000;

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->role === 'owner', 403);

        $periode = $this->resolvePeriode($request);
        $settings = AttendanceSetting::current();

        $tetapEmployees = Employee::where('employee_type', 'tetap')
            ->with('payrollComponent')
            ->get()
            ->map(fn ($employee) => $this->attachTetapSummary($employee, $periode, $settings));

        $partTimeEmployees = Employee::where('employee_type', 'part_time')
            ->with('payrollComponent')
            ->get()
            ->map(fn ($employee) => $this->attachPartTimeSummary($employee, $periode));

        return view('owner.payroll', [
            'tetapEmployees' => $tetapEmployees,
            'partTimeEmployees' => $partTimeEmployees,
            'periodeLabel' => $periode->translatedFormat('F Y'),
            'periodeValue' => $periode->format('Y-m'),
            'isCurrentPeriode' => $periode->isSameMonth(Carbon::now()),
            'rateMakanBensin' => self::RATE_MAKAN_BENSIN_PART_TIME,
        ]);
    }

    public function updateComponent(UpdatePayrollComponentRequest $request, Employee $employee): RedirectResponse
    {
        PayrollComponent::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'base_salary' => $request->base_salary,
                'meal_rate' => $request->meal_rate,
                'transport_rate' => $request->transport_rate,
                'hourly_rate' => $request->hourly_rate,
                'allowance' => $request->allowance,
                'thr_active' => $request->boolean('thr_active'),
                'effective_date' => now()->format('Y-m-d'),
            ]
        );

        // kembali ke periode yang sedang dilihat, bukan selalu bulan berjalan
        return back()->with('success', "Komponen gaji {$employee->full_name} disimpan.");
    }

    public function publishAll(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->role === 'owner', 403);

        $periode = $this->resolvePeriode($request);
        $settings = AttendanceSetting::current();

        $employees = Employee::with('payrollComponent')->get();

        foreach ($employees as $employee) {
            if (!$employee->payrollComponent) {
                continue;
            }

            if ($employee->employee_type === 'tetap') {
                $data = $this->attachTetapSummary($employee, $periode, $settings);
                $pendapatan = $data->payrollComponent->base_salary
                    + ($data->payrollComponent->meal_rate * $data->hari_hadir)
                    + ($data->payrollComponent->transport_rate * $data->hari_hadir)
                    + $data->payrollComponent->allowance;
                $potongan = $data->potongan_telat;
                $hariHadir = $data->hari_hadir;
            } else {
                $data = $this->attachPartTimeSummary($employee, $periode);
                $pendapatan = ($data->payrollComponent->base_salary ?? 0)
                    + ($data->hari_hadir * self::RATE_MAKAN_BENSIN_PART_TIME)
                    + ($data->payrollComponent->allowance ?? 0);
                $potongan = 0;
                $hariHadir = $data->hari_hadir;
            }

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
                    'total_diterima' => $pendapatan - $potongan,
                    'published_at' => now(),
                ]
            );
        }

        return redirect()
            ->route('payroll.index', ['periode' => $periode->format('Y-m')])
            ->with('success', "Slip gaji periode {$periode->translatedFormat('F Y')} berhasil diterbitkan untuk semua karyawan.");
    }

    /**
     * Halaman riwayat: daftar periode yang sudah pernah diterbitkan,
     * dan detail slip per karyawan untuk periode terpilih.
     */
    public function history(Request $request): View
    {
        abort_unless(auth()->user()->role === 'owner', 403);

        $periods = Payslip::selectRaw('period_year, period_month')
            ->distinct()
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get();

        $selectedYear = (int) $request->query('year', optional($periods->first())->period_year);
        $selectedMonth = (int) $request->query('month', optional($periods->first())->period_month);

        $payslips = Payslip::with('employee')
            ->where('period_year', $selectedYear)
            ->where('period_month', $selectedMonth)
            ->orderBy('employee_id')
            ->get();

        $periodeLabel = $selectedYear && $selectedMonth
            ? Carbon::createFromDate($selectedYear, $selectedMonth, 1)->translatedFormat('F Y')
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
     * Ambil periode (bulan & tahun) dari query string ?periode=Y-m,
     * fallback ke bulan berjalan kalau tidak ada / format salah.
     */
    private function resolvePeriode(Request $request): Carbon
    {
        $periode = $request->query('periode');

        if ($periode) {
            try {
                return Carbon::createFromFormat('Y-m', $periode)->startOfMonth();
            } catch (\Exception $e) {
                // format tidak valid, jatuh ke default di bawah
            }
        }

        return Carbon::now()->startOfMonth();
    }

    private function attachTetapSummary(Employee $employee, Carbon $periode, AttendanceSetting $settings): Employee
    {
        $hariHadir = Attendance::where('employee_id', $employee->id)
            ->whereMonth('tanggal', $periode->month)
            ->whereYear('tanggal', $periode->year)
            ->whereIn('status', ['tepat_waktu', 'terlambat'])
            ->count();

        $totalTelatMenit = Attendance::where('employee_id', $employee->id)
            ->whereMonth('tanggal', $periode->month)
            ->whereYear('tanggal', $periode->year)
            ->sum('late_minutes');

        $employee->hari_hadir = $hariHadir;
        $employee->potongan_telat = $totalTelatMenit * $settings->late_deduction_per_minute;

        return $employee;
    }

    private function attachPartTimeSummary(Employee $employee, Carbon $periode): Employee
    {
        $hariHadir = Attendance::where('employee_id', $employee->id)
            ->whereMonth('tanggal', $periode->month)
            ->whereYear('tanggal', $periode->year)
            ->whereIn('status', ['tepat_waktu', 'terlambat'])
            ->distinct('tanggal')
            ->count('tanggal');

        $employee->hari_hadir = $hariHadir;
        $employee->makan_bensin = $hariHadir * self::RATE_MAKAN_BENSIN_PART_TIME;

        return $employee;
    }
}