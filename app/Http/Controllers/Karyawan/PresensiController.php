<?php

namespace App\Http\Controllers\Karyawan;

use App\Exceptions\AttendanceException;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shift;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PresensiController extends Controller
{
    private const DAY_ORDER = [
        'senin' => 1, 'selasa' => 2, 'rabu' => 3, 'kamis' => 4,
        'jumat' => 5, 'sabtu' => 6, 'minggu' => 7,
    ];

    public function __construct(private readonly AttendanceService $service)
    {
    }

    public function __invoke(Request $request): View
    {
        $employee = Employee::with('branch')->findOrFail(Auth::user()->employee_id);
        $now = Carbon::now();
        $tanggal = $now->toDateString();
        $todayKey = array_flip(self::DAY_ORDER)[$now->dayOfWeekIso] ?? null;

        // Hanya shift yang berlaku untuk hari ini.
        $shifts = Shift::orderBy('start_time')
            ->get()
            ->filter(fn ($s) => in_array($todayKey, $s->applicable_days ?? [], true))
            ->values();

        $todayAttendance = Attendance::where('employee_id', $employee->id)
            ->where('tanggal', $tanggal)
            ->whereNotNull('shift_id')
            ->first();

        // Riwayat: minggu yang dilihat (default minggu ini, bisa mundur lewat ?minggu=Y-m-d)
        $thisWeek = $now->copy()->startOfWeek();
        $weekStart = $this->resolveWeekStart($request->query('minggu'), $thisWeek);
        $weekEnd = $weekStart->copy()->endOfWeek();
        $isCurrentWeek = $weekStart->isSameDay($thisWeek);
        $weeksAgo = (int) floor($thisWeek->diffInDays($weekStart, true) / 7);

        $weekAttendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('tanggal', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->whereNotNull('shift_id')
            ->orderByDesc('tanggal')
            ->get();

        // Tombol "sebelumnya" hanya aktif kalau masih ada presensi di minggu-minggu sebelumnya
        $earliest = Attendance::where('employee_id', $employee->id)
            ->whereNotNull('shift_id')
            ->min('tanggal');

        $prevWeek = ($earliest && Carbon::parse($earliest)->startOfWeek()->lt($weekStart))
            ? $weekStart->copy()->subWeek()->toDateString()
            : null;

        $nextWeek = $isCurrentWeek ? null : $weekStart->copy()->addWeek()->toDateString();

        return view('karyawan.presensi', [
            'employee' => $employee,
            'shifts' => $shifts,
            'todayAttendance' => $todayAttendance,
            'weekAttendances' => $weekAttendances,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'isCurrentWeek' => $isCurrentWeek,
            'weeksAgo' => $weeksAgo,
            'prevWeek' => $prevWeek,
            'nextWeek' => $nextWeek,
        ]);
    }

    /**
     * Ubah parameter ?minggu=Y-m-d menjadi hari Senin minggu tsb.
     * Input tidak valid atau di masa depan -> minggu ini.
     */
    private function resolveWeekStart(mixed $param, Carbon $thisWeek): Carbon
    {
        if (! is_string($param) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $param)) {
            return $thisWeek->copy();
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $param)->startOfWeek();
        } catch (\Throwable) {
            return $thisWeek->copy();
        }

        return $date->gt($thisWeek) ? $thisWeek->copy() : $date;
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $employee = Employee::findOrFail(Auth::user()->employee_id);

        try {
            $data = $request->validate([
                'shift_id' => ['required', 'integer', 'exists:shifts,id'],
                'photo' => ['required', 'image', 'max:5120'],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
            ], [
                'shift_id.required' => 'Pilih shift terlebih dahulu.',
                'photo.required' => 'Foto dari kamera wajib diambil sebelum menyimpan presensi.',
                'latitude.required' => 'Lokasi tidak terdeteksi, aktifkan GPS/izinkan akses lokasi.',
                'longitude.required' => 'Lokasi tidak terdeteksi, aktifkan GPS/izinkan akses lokasi.',
            ]);

            $this->service->checkInTetap(
                $employee, (int) $data['shift_id'], $request->file('photo'),
                (float) $data['latitude'], (float) $data['longitude']
            );
        } catch (AttendanceException $e) {
            return back()->withErrors(['attendance' => $e->getMessage()])->withInput();
        }

        return redirect()->route('presensi')->with('success', 'Presensi masuk berhasil dicatat.');
    }

    public function checkOut(Request $request): RedirectResponse
    {
        $employee = Employee::findOrFail(Auth::user()->employee_id);

        try {
            $request->validate([
                'photo' => ['required', 'image', 'max:5120'],
            ], [
                'photo.required' => 'Foto dari kamera wajib diambil sebelum menyimpan presensi.',
            ]);

            $this->service->checkOutTetap($employee, $request->file('photo'));
        } catch (AttendanceException $e) {
            return back()->withErrors(['attendance' => $e->getMessage()]);
        }

        return redirect()->route('presensi')->with('success', 'Presensi pulang berhasil dicatat.');
    }
}