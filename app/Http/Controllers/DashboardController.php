<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\ClientVisit;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return $user->role === 'owner'
            ? $this->ownerDashboard()
            : $this->karyawanDashboard($user->employee);
    }

    /**
     * Beranda Owner - ringkasan lintas seluruh karyawan.
     */
    private function ownerDashboard(): View
    {
        $today = Carbon::today();

        $totalKaryawan = Employee::count();

        $absenHariIni = Attendance::whereDate('tanggal', $today)
            ->whereIn('status', ['tepat_waktu', 'terlambat'])
            ->distinct('employee_id')
            ->count('employee_id');

        $kunjunganHariIni = ClientVisit::whereDate('visited_at', $today)->count();

        $izinMenunggu = LeaveRequest::where('status', 'menunggu')->count();

        // Aktivitas terbaru tim: gabungan presensi + kunjungan klien hari ini,
        // diurutkan dari yang paling baru.
        $presensiHariIni = Attendance::whereDate('tanggal', $today)
            ->whereNotNull('check_in')
            ->with('employee')
            ->get()
            ->map(function (Attendance $a) {
                return [
                    'title' => "{$a->employee->full_name} — Absen masuk",
                    'sub' => $a->status === 'tepat_waktu'
                        ? "Tepat waktu · radius {$a->distance_m} m"
                        : "Terlambat {$a->late_minutes} menit · radius {$a->distance_m} m",
                    'dot' => $a->status === 'tepat_waktu' ? 'var(--green)' : 'var(--rust)',
                    'time' => Carbon::parse($a->check_in)->format('H:i'),
                    'sort' => Carbon::parse($a->check_in),
                ];
            });

        $kunjunganTim = ClientVisit::whereDate('visited_at', $today)
            ->with('employee')
            ->get()
            ->map(function (ClientVisit $v) {
                return [
                    'title' => "{$v->employee->full_name} — Kunjungan Klien",
                    'sub' => "{$v->client_name} · {$v->visit_type}",
                    'dot' => 'var(--gold)',
                    'time' => Carbon::parse($v->visited_at)->format('H:i'),
                    'sort' => Carbon::parse($v->visited_at),
                ];
            });

        $aktivitasTim = $presensiHariIni->concat($kunjunganTim)
            ->sortByDesc('sort')
            ->take(8)
            ->values();

        return view('owner.beranda', [
            'totalKaryawan' => $totalKaryawan,
            'absenHariIni' => $absenHariIni,
            'kunjunganHariIni' => $kunjunganHariIni,
            'izinMenunggu' => $izinMenunggu,
            'aktivitasTim' => $aktivitasTim,
            'tanggalHariIni' => $today->translatedFormat('l, d F Y'),
        ]);
    }

    /**
     * Beranda Karyawan - ringkasan presensi & aktivitas diri sendiri hari ini.
     */
    private function karyawanDashboard(Employee $employee): View
    {
        $today = Carbon::today();
        $settings = AttendanceSetting::current();

        $presensiHariIni = Attendance::where('employee_id', $employee->id)
            ->whereDate('tanggal', $today)
            ->orderBy('check_in')
            ->get();

        $absenMasuk = $presensiHariIni->first();
        $absenPulang = $presensiHariIni->last();

        $keterlambatanBulanIni = Attendance::where('employee_id', $employee->id)
            ->whereMonth('tanggal', $today->month)
            ->whereYear('tanggal', $today->year)
            ->where('status', 'terlambat')
            ->get();

        $jumlahTelat = $keterlambatanBulanIni->count();
        $totalMenitTelat = $keterlambatanBulanIni->sum('late_minutes');
        $potonganTelat = $totalMenitTelat * $settings->late_deduction_per_minute;

        // Aktivitas hari ini: presensi + kunjungan klien milik sendiri
        $kunjunganHariIni = ClientVisit::where('employee_id', $employee->id)
            ->whereDate('visited_at', $today)
            ->get();

        $aktivitas = collect();

        foreach ($presensiHariIni as $p) {
            if ($p->check_in) {
                $aktivitas->push([
                    'title' => $employee->employee_type === 'tetap'
                        ? 'Absen masuk — '.($p->shiftSchedule?->shift?->name ?? 'Shift')
                        : 'Absen masuk — '.($p->activity ?? 'Sesi mengajar'),
                    'sub' => 'Terverifikasi foto & radius '.number_format($p->distance_m, 0).' m',
                    'dot' => 'var(--green)',
                    'time' => Carbon::parse($p->check_in)->format('H:i'),
                    'sort' => Carbon::parse($p->check_in),
                ]);
            }
            if ($p->check_out) {
                $aktivitas->push([
                    'title' => 'Absen pulang',
                    'sub' => 'Tervalidasi otomatis',
                    'dot' => 'var(--text-faint)',
                    'time' => Carbon::parse($p->check_out)->format('H:i'),
                    'sort' => Carbon::parse($p->check_out),
                ]);
            }
        }

        foreach ($kunjunganHariIni as $v) {
            $aktivitas->push([
                'title' => "Kunjungan klien — {$v->client_name}",
                'sub' => "Foto lokasi tersimpan · {$v->address}",
                'dot' => 'var(--gold)',
                'time' => Carbon::parse($v->visited_at)->format('H:i'),
                'sort' => Carbon::parse($v->visited_at),
            ]);
        }

        $aktivitas = $aktivitas->sortByDesc('sort')->values();

        return view('karyawan.beranda', [
            'employee' => $employee,
            'absenMasuk' => $absenMasuk,
            'absenPulang' => $absenPulang,
            'jumlahTelat' => $jumlahTelat,
            'potonganTelat' => $potonganTelat,
            'aktivitas' => $aktivitas,
            'tanggalHariIni' => $today->translatedFormat('l, d F Y'),
            'sapaan' => now()->hour < 11 ? 'pagi' : (now()->hour < 15 ? 'siang' : (now()->hour < 18 ? 'sore' : 'malam')),
        ]);
    }
}