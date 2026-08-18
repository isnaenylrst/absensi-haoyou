<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use App\Models\PayrollComponent;
use App\Models\Payslip;
use App\Models\ThrRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class PayslipController extends Controller
{
    /**
     * Halaman "Gaji Saya" - KHUSUS karyawan, read-only.
     */
    public function index(): View|\Illuminate\Http\RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $employee = $user->employee;

        if ($user->role === 'owner') {
            return view('owner.gaji-saya-locked');
        }

        $currentMonth = now()->month;
        $currentYear = now()->year;

        $payslip = Payslip::where('employee_id', $employee->id)
            ->where('period_month', $currentMonth)
            ->where('period_year', $currentYear)
            ->first();

        $payrollComponent = PayrollComponent::where('employee_id', $employee->id)->first();

        $thrRecord = ThrRecord::where('employee_id', $employee->id)
            ->where('year', $currentYear)
            ->first();

        $settings = AttendanceSetting::current();

        // ============================================================
        // Hitung masa kerja & kelayakan THR
        // ============================================================
        $joinDate = Carbon::parse($employee->join_date);
        $now = Carbon::now();
        $diff = $joinDate->diff($now); 
        $masaKerjaTahun = $diff->y;
        $masaKerjaBulan = $diff->m;

        $tahunKerjaKe = $masaKerjaTahun + 1; 

        $thrEligible = $thrRecord
            ? $thrRecord->eligible
            : ($tahunKerjaKe >= $settings->thr_start_year + 1);

        $thrEstimasi = $thrRecord?->amount ?? ($payrollComponent->base_salary ?? 0);
       
        $rincian = null;
        if ($payslip && $payrollComponent) {
            $hariHadir = $payslip->hari_hadir;

            $rincian = [
                'gaji_pokok' => $payrollComponent->base_salary,
                'uang_makan' => $payrollComponent->meal_rate * $hariHadir,
                'uang_bensin' => $payrollComponent->transport_rate * $hariHadir,
                'tunjangan' => $payrollComponent->allowance,
                'potongan' => $payslip->total_potongan,
            ];
        }

        return view('karyawan.gaji-saya', [
            'employee' => $employee,
            'payslip' => $payslip,
            'payrollComponent' => $payrollComponent,
            'rincian' => $rincian,
            'thrEligible' => $thrEligible,
            'thrEstimasi' => $thrEstimasi,
            'masaKerjaTahun' => $masaKerjaTahun,
            'masaKerjaBulan' => $masaKerjaBulan,
            'periodeLabel' => $now->translatedFormat('F Y'),
        ]);
    }
}