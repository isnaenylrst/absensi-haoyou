<?php

namespace App\Http\Controllers\Karyawan;

use App\Exceptions\AttendanceException;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PartTimeSchedule;
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

    public function __invoke(): View
    {
        $employee = Employee::with('branch')->findOrFail(Auth::user()->employee_id);
        $now = Carbon::now();
        $tanggal = $now->toDateString();
        $todayKey = array_flip(self::DAY_ORDER)[$now->dayOfWeekIso];

        $shifts = collect();
        $todayAttendance = null;   // karyawan tetap: 1 record atau null
        $weekAttendances = collect();
        $todayAttendances = collect(); // part-time: bisa banyak sesi
        $weekSchedules = collect();
        $recentAttendances = collect();
        $canSubmitTeachingSessions = (bool) $employee->can_submit_teaching_sessions;

        if ($employee->employee_type === 'part_time' || $canSubmitTeachingSessions) {
            $todayAttendances = Attendance::where('employee_id', $employee->id)
                ->where('tanggal', $tanggal)
            ->whereNull('shift_id')
                ->orderBy('check_in')
                ->get();

            $weekSchedules = PartTimeSchedule::where('employee_id', $employee->id)
                ->get()
                ->sortBy(fn ($s) => sprintf('%d-%s', self::DAY_ORDER[$s->day_of_week] ?? 9, $s->start_time));

            $recentAttendances = Attendance::where('employee_id', $employee->id)
                ->whereNull('shift_id')
                ->orderByDesc('tanggal')
                ->orderByDesc('check_in')
                ->limit(10)
                ->get();
        }

        if ($employee->employee_type !== 'part_time') {
            // Hanya tampilkan shift yang berlaku untuk hari ini (mis. Sabtu -> hanya shift *Sabtu)
            $shifts = Shift::orderBy('start_time')
                ->get()
                ->filter(fn ($s) => in_array($todayKey, $s->applicable_days ?? [], true))
                ->values();

            $todayAttendance = Attendance::where('employee_id', $employee->id)
                ->where('tanggal', $tanggal)
                ->whereNotNull('shift_id')
                ->first();

            $weekAttendances = Attendance::where('employee_id', $employee->id)
                ->whereBetween('tanggal', [
                    $now->copy()->startOfWeek()->toDateString(),
                    $now->copy()->endOfWeek()->toDateString(),
                ])
                ->whereNotNull('shift_id')
                ->orderByDesc('tanggal')
                ->get();
        }

        return view('karyawan.presensi', [
            'employee' => $employee,
            'shifts' => $shifts,
            'todayAttendance' => $todayAttendance,
            'weekAttendances' => $weekAttendances,
            'todayAttendances' => $todayAttendances,
            'weekSchedules' => $weekSchedules,
            'recentAttendances' => $recentAttendances,
            'canSubmitTeachingSessions' => $canSubmitTeachingSessions,
        ]);
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $employee = Employee::findOrFail(Auth::user()->employee_id);
        $attendanceMode = $request->input('attendance_mode');
        $isTeachingSession = $employee->employee_type === 'part_time'
            || ($attendanceMode === 'teaching' && $employee->can_submit_teaching_sessions);

        try {
            if ($isTeachingSession) {
                $data = $request->validate([
                    'sessions' => ['required', 'array', 'min:1', 'max:20'],
                    'sessions.*.start_time' => ['required', 'date_format:H:i'],
                    'sessions.*.end_time' => ['required', 'date_format:H:i', 'after:sessions.*.start_time'],
                    'sessions.*.activity' => ['required', 'string', 'max:150'],
                ], [
                    'sessions.required' => 'Tambahkan minimal satu sesi presensi.',
                    'sessions.*.end_time.after' => 'Jam selesai harus setelah jam mulai.',
                    'sessions.*.activity.required' => 'Kegiatan/keterangan wajib diisi.',
                ]);

                $this->service->submitSesiPartTimeBatch($employee, $data['sessions']);

                $message = count($data['sessions']) . ' sesi presensi berhasil dikirim.';
            } else {
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

                $message = 'Presensi masuk berhasil dicatat.';
            }
        } catch (AttendanceException $e) {
            return back()->withErrors(['attendance' => $e->getMessage()])->withInput();
        }

        return redirect()->route('presensi')->with('success', $message);
    }

    public function checkOut(Request $request): RedirectResponse
    {
        $employee = Employee::findOrFail(Auth::user()->employee_id);

        if ($employee->employee_type === 'part_time') {
            return back()->withErrors(['attendance' => 'Karyawan part-time tidak menggunakan check-out terpisah.']);
        }

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