<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use App\Models\PayrollComponent;
use App\Models\Payslip;
use App\Models\ThrRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PayslipController extends Controller
{
    /**
     * Halaman "Gaji Saya" - KHUSUS karyawan, read-only.
     */
    public function index(): View|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $employee = $user->employee;

        if ($user->role === 'owner') {
            return view('owner.gaji-saya-locked');
        }

        $data = $this->buildPayslipData($employee, now()->month, now()->year);

        return view('karyawan.gaji-saya', $data);
    }

    /**
     * Download slip gaji bulan berjalan sebagai PDF.
     */
    public function downloadPdf(): Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        abort_unless($user->role !== 'owner', 403);

        $employee = $user->employee;
        $data = $this->buildPayslipData($employee, now()->month, now()->year);

        abort_if(!$data['payslip'], 404, 'Slip gaji periode ini belum diterbitkan.');

        $pdf = Pdf::loadView('pdf.slip-gaji', $data)->setPaper('a4', 'portrait');

        $filename = 'Slip-Gaji-'.$employee->employee_code.'-'.now()->format('m-Y').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Hitung semua data yang dibutuhkan halaman Gaji Saya & PDF slip gaji,
     * supaya tidak duplikat logic antara index() dan downloadPdf().
     */
    private function buildPayslipData($employee, int $month, int $year): array
    {
        $payslip = Payslip::where('employee_id', $employee->id)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->first();

        $payrollComponent = PayrollComponent::where('employee_id', $employee->id)->first();

        $thrRecord = ThrRecord::where('employee_id', $employee->id)
            ->where('year', $year)
            ->first();

        $settings = AttendanceSetting::current();

        // ============================================================
        // Hitung masa kerja & kelayakan THR (pakai DateInterval bawaan
        // PHP via ->diff(), BUKAN diffInYears()/diffInMonths() - supaya
        // hasilnya selalu angka bulat, bukan desimal panjang).
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

        // ============================================================
        // Pecah rincian pendapatan dari komponen gaji + hari hadir slip
        // ============================================================
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

        return [
            'employee' => $employee,
            'payslip' => $payslip,
            'payrollComponent' => $payrollComponent,
            'rincian' => $rincian,
            'thrEligible' => $thrEligible,
            'thrEstimasi' => $thrEstimasi,
            'masaKerjaTahun' => $masaKerjaTahun,
            'masaKerjaBulan' => $masaKerjaBulan,
            'periodeLabel' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
        ];
    }
}