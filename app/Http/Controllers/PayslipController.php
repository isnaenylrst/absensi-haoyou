<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use App\Models\PayrollComponent;
use App\Models\Payslip;
use App\Models\ThrRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PayslipController extends Controller
{
    /**
     * Halaman "Gaji Saya" - KHUSUS karyawan, read-only.
     * Pilih periode lewat ?periode=YYYY-MM. Default: slip terbaru yang sudah ada.
     */
    public function index(Request $request): View|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $employee = $user->employee;

        if ($user->role === 'owner') {
            return view('owner.gaji-saya-locked');
        }

        $payslip = $this->resolvePayslip($employee, $request->query('periode'));

        $data = $this->buildPayslipData($employee, $payslip);

        return view('karyawan.gaji-saya', $data);
    }

    /**
     * Download slip gaji (sesuai periode yang dipilih) sebagai PDF.
     */
    public function downloadPdf(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        abort_unless($user->role !== 'owner', 403);

        $employee = $user->employee;

        $payslip = $this->resolvePayslip($employee, $request->query('periode'), false);

        abort_if(!$payslip, 404, 'Slip gaji periode ini belum diterbitkan.');

        $data = $this->buildPayslipData($employee, $payslip);

        $pdf = Pdf::loadView('pdf.slip-gaji', $data)->setPaper('a4', 'portrait');

        $filename = 'Slip-Gaji-'.$employee->employee_code.'-'
            .str_pad($payslip->period_month, 2, '0', STR_PAD_LEFT).'-'
            .$payslip->period_year.'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Tentukan slip yang dipilih.
     * - Ada ?periode=YYYY-MM valid & slipnya ada -> slip itu.
     * - Selain itu -> slip terbaru.
     */
    private function resolvePayslip($employee, ?string $periode, bool $fallbackLatest = true): ?Payslip
    {
        if ($periode && preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $periode, $m)) {
            $slip = Payslip::where('employee_id', $employee->id)
                ->where('period_year', (int) $m[1])
                ->where('period_month', (int) $m[2])
                ->first();

            if ($slip || !$fallbackLatest) {
                return $slip;
            }
        }

        return $this->latestPayslip($employee);
    }

    private function latestPayslip($employee): ?Payslip
    {
        return Payslip::where('employee_id', $employee->id)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->first();
    }

    /**
     * Keterangan periode untuk slip KARYAWAN TETAP:
     * rentang tanggal, hari Minggu, tanggal merah, hari efektif,
     * hari tidak masuk, hari terlambat, dan keterangan bonus kerajinan.
     */
    private function buildKeterangan(Payslip $payslip): array
    {
        $awal = Carbon::createFromDate($payslip->period_year, $payslip->period_month, 1)->startOfDay();
        $jumlahHari = $awal->daysInMonth;

        // Hari Minggu (Sabtu tetap dihitung hari kerja)
        $minggu = [];
        for ($d = 1; $d <= $jumlahHari; $d++) {
            if ($awal->copy()->day($d)->isSunday()) {
                $minggu[] = $d;
            }
        }

        // NULL = slip lama (diterbitkan sebelum fitur keterangan ada)
        $libur = $payslip->tanggal_libur;
        $jumlahLibur = is_array($libur) ? count($libur) : 0;

        $hitungEfektif = $jumlahHari - count($minggu) - $jumlahLibur;

        $rumus = $jumlahHari.' − '.count($minggu);
        if ($jumlahLibur > 0) {
            $rumus .= ' − '.$jumlahLibur;
        }
        $rumus .= ' = '.$hitungEfektif.' hari';

        $hariEfektif = (int) $payslip->hari_efektif;
        $tidakMasuk = max(0, $hariEfektif - (int) $payslip->hari_hadir);

        $terlambat = $payslip->tanggal_terlambat; // NULL = slip lama

        $bonusDibayar = (float) $payslip->bonus_kerajinan;
        $bonusAwal = $terlambat === null
            ? $bonusDibayar
            : (float) $payslip->bonus_kerajinan_awal;

        return [
            'periode_label' => '1 – '.$jumlahHari.' '.$awal->translatedFormat('F Y'),
            'bulan_nama' => $awal->translatedFormat('F'),
            'jumlah_hari' => $jumlahHari,
            'minggu' => $minggu,
            'libur' => $libur,
            'rumus_efektif' => $rumus,
            'hitung_efektif' => $hitungEfektif,
            'hari_efektif' => $hariEfektif,
            'tidak_masuk' => $tidakMasuk,
            'terlambat' => $terlambat,
            'bonus_awal' => $bonusAwal,
            'bonus_dibayar' => $bonusDibayar,
            'bonus_dipotong' => max(0, $bonusAwal - $bonusDibayar),
        ];
    }

    /**
     * Hitung semua data yang dibutuhkan halaman Gaji Saya & PDF slip gaji.
     */
    private function buildPayslipData($employee, ?Payslip $payslip): array
    {
        $payrollComponent = PayrollComponent::where('employee_id', $employee->id)->first();

        $thrYear = $payslip?->period_year ?? now()->year;

        $thrRecord = ThrRecord::where('employee_id', $employee->id)
            ->where('year', $thrYear)
            ->first();

        $settings = AttendanceSetting::current();

        // Masa kerja & kelayakan THR (selalu berdasarkan hari ini)
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

        // Rincian KARYAWAN TETAP - dari angka yang tersimpan di payslips
        $rincian = null;
        $tarifMakan = 0;
        $tarifBensin = 0;
        $keterangan = null;

        if ($payslip && $employee->employee_type === 'tetap') {
            $rincian = [
                'gaji_pokok' => $payslip->gaji_pokok,
                'uang_makan' => $payslip->uang_makan,
                'uang_bensin' => $payslip->uang_bensin,
                'bonus_kerajinan' => $payslip->bonus_kerajinan,
                'bonus_kinerja' => $payslip->bonus_kinerja,
                'thr' => $payslip->thr,
                'potongan' => $payslip->potongan_telat,
            ];

            if ($payslip->hari_hadir > 0) {
                $tarifMakan = (float) $payslip->uang_makan / $payslip->hari_hadir;
                $tarifBensin = (float) $payslip->uang_bensin / $payslip->hari_hadir;
            } else {
                $tarifMakan = (float) ($payrollComponent->meal_rate ?? 0);
                $tarifBensin = (float) ($payrollComponent->transport_rate ?? 0);
            }

            $keterangan = $this->buildKeterangan($payslip);
        }

        // Rincian PART TIME
        $partTime = null;
        if ($payslip && $employee->employee_type === 'part_time') {
            $fee = (float) $payslip->gaji_pokok;
            $makan = (float) $payslip->uang_makan;
            $bensin = (float) $payslip->uang_bensin;

            if ($fee + $makan + $bensin == 0) {
                $fee = (float) ($payrollComponent->base_salary ?? 0);
                $makan = (float) ($payrollComponent->meal_rate ?? 0);
                $bensin = (float) ($payrollComponent->transport_rate ?? 0);
            }

            $partTime = [
                'fee' => $fee,
                'makan' => $makan,
                'bensin' => $bensin,
                'total' => $fee + $makan + $bensin,
            ];
        }

        // Riwayat semua slip (dropdown & tabel riwayat)
        $riwayat = Payslip::where('employee_id', $employee->id)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get()
            ->map(function (Payslip $p) {
                $tgl = Carbon::createFromDate($p->period_year, $p->period_month, 1);

                return [
                    'key' => sprintf('%04d-%02d', $p->period_year, $p->period_month),
                    'label' => $tgl->translatedFormat('F Y'),
                    'total' => $p->total_diterima,
                    'published_at' => $p->published_at,
                ];
            });

        $periodeKey = $payslip
            ? sprintf('%04d-%02d', $payslip->period_year, $payslip->period_month)
            : null;

        $periodeLabel = $payslip
            ? Carbon::createFromDate($payslip->period_year, $payslip->period_month, 1)->translatedFormat('F Y')
            : now()->translatedFormat('F Y');

        return [
            'employee' => $employee,
            'payslip' => $payslip,
            'payrollComponent' => $payrollComponent,
            'rincian' => $rincian,
            'partTime' => $partTime,
            'keterangan' => $keterangan,
            'tarifMakan' => $tarifMakan,
            'tarifBensin' => $tarifBensin,
            'thrEligible' => $thrEligible,
            'thrEstimasi' => $thrEstimasi,
            'masaKerjaTahun' => $masaKerjaTahun,
            'masaKerjaBulan' => $masaKerjaBulan,
            'periodeLabel' => $periodeLabel,
            'periodeKey' => $periodeKey,
            'riwayat' => $riwayat,
        ];
    }
}