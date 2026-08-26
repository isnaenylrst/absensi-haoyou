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

    private const HARI_LABEL = [
        'senin' => 'Senin', 'selasa' => 'Selasa', 'rabu' => 'Rabu',
        'kamis' => 'Kamis', 'jumat' => 'Jumat', 'sabtu' => 'Sabtu', 'minggu' => 'Minggu',
    ];

    public function __construct(private readonly AttendanceService $service)
    {
    }

    public function __invoke(): View
    {
        $employee = Employee::with('branch')->findOrFail(Auth::user()->employee_id);
        $now = Carbon::now();
        $tanggal = $now->toDateString();
        $todayKey = array_flip(self::DAY_ORDER)[$now->dayOfWeekIso] ?? null;

        $shifts = collect();
        $todayAttendance = null;       // karyawan tetap: 1 record absen kamera+GPS hari ini, atau null
        $weekAttendances = collect();  // karyawan tetap: riwayat absen kamera+GPS minggu ini
        $todayAttendances = collect(); // guru (part-time ATAU tetap yg can_submit_teaching_sessions): sesi mengajar hari ini
        $weekSchedules = collect();    // guru: jadwal REFERENSI mingguan (rekuren, bukan log kehadiran)
        $weekSchedulesByDay = collect(); // guru: $weekSchedules yang sudah dikelompokkan per day_of_week, siap dirender
        $recentAttendances = collect(); // guru: riwayat sesi mengajar yang sudah disubmit
        $canSubmitTeachingSessions = (bool) $employee->can_submit_teaching_sessions;

        // ====================================================================
        // BLOK GURU: berlaku untuk part-time MAUPUN karyawan tetap berposisi
        // guru (mis. Fitri Maulidah / Dwi Ayu Wulandari -> employee_type
        // 'tetap' tapi can_submit_teaching_sessions = true karena position
        // = 'Teacher'). Dua kondisi ini SENGAJA tidak eksklusif terhadap
        // blok "karyawan tetap" di bawah -- guru tetap bisa punya keduanya.
        // ====================================================================
        if ($employee->employee_type === 'part_time' || $canSubmitTeachingSessions) {

            // FIX: part_time_schedules TIDAK punya kolom `tanggal` -- ini
            // jadwal rekuren per day_of_week (berulang tiap minggu), bukan
            // log presensi per tanggal kalender. Jadi diambil semua &
            // diurutkan berdasar hari + jam mulai, tanpa filter tanggal.
            $weekSchedules = PartTimeSchedule::where('employee_id', $employee->id)
                ->get()
                ->sortBy(fn ($s) => sprintf('%02d %s', self::DAY_ORDER[$s->day_of_week] ?? 99, $s->start_time))
                ->map(fn ($s) => (object) [
                    'day_of_week' => $s->day_of_week,
                    'hari_label' => self::HARI_LABEL[$s->day_of_week] ?? ucfirst($s->day_of_week),
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                    'activity' => $s->activity,
                ])
                ->values();

            // Dikelompokkan per hari di sini (bukan di Blade via @php) supaya
            // view tinggal render tanpa logic tambahan.
            $weekSchedulesByDay = $weekSchedules->groupBy('day_of_week');

            // FIX: presensi/kehadiran AKTUAL (hasil submit) ada di tabel
            // attendances, bukan part_time_schedules. shift_id NULL dipakai
            // untuk memisahkan "sesi mengajar" dari "absen kamera+GPS
            // kantor" -- penting khusus utk guru TETAP yang bisa punya
            // kedua jenis presensi di tanggal yang sama. Guru bisa >1 sesi
            // sehari, jadi ambil semua baris (jangan keyBy tanggal).
            $todayAttendances = Attendance::where('employee_id', $employee->id)
                ->whereDate('tanggal', $tanggal)
                ->whereNull('shift_id')
                ->orderBy('check_in')
                ->get();

            $recentAttendances = Attendance::where('employee_id', $employee->id)
                ->whereNull('shift_id')
                ->orderByDesc('tanggal')
                ->orderByDesc('check_in')
                ->limit(10)
                ->get();
        }

        // ====================================================================
        // BLOK KARYAWAN TETAP: absen kamera + GPS berdasarkan shift.
        // Berlaku untuk semua employee_type != 'part_time', termasuk guru
        // tetap (mis. Fitri) yang tetap wajib absen kantor selain sesi
        // mengajarnya.
        // ====================================================================
        if ($employee->employee_type !== 'part_time') {
            // Hanya shift yang berlaku untuk hari ini.
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
            'weekSchedulesByDay' => $weekSchedulesByDay,
            'recentAttendances' => $recentAttendances,
            'canSubmitTeachingSessions' => $canSubmitTeachingSessions,
        ]);
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $employee = Employee::findOrFail(Auth::user()->employee_id);
        $attendanceMode = $request->input('attendance_mode');

        // Sesi mengajar dipakai kalau: karyawan part-time (selalu lewat
        // jalur ini), ATAU karyawan tetap yang eksplisit submit lewat form
        // "Sesi Mengajar" (attendance_mode = teaching) dan memang punya
        // hak can_submit_teaching_sessions (mis. guru tetap).
        $isTeachingSession = $employee->employee_type === 'part_time'
            || ($attendanceMode === 'teaching' && $employee->can_submit_teaching_sessions);

        try {
            if ($isTeachingSession) {
                $data = $request->validate([
                    'sessions' => ['required', 'array', 'min:1', 'max:20'],
                    'sessions.*.start_time' => ['required', 'date_format:H:i'],
                    'sessions.*.end_time' => ['required', 'date_format:H:i', 'after:sessions.*.start_time'],
                    'tanggal' => ['required', 'date', 'before_or_equal:today'],
                    'sessions.*.activity' => ['required', 'string', 'max:150'],
                ], [
                    'sessions.required' => 'Tambahkan minimal satu sesi presensi.',
                    'sessions.*.end_time.after' => 'Jam selesai harus setelah jam mulai.',
                    'sessions.*.activity.required' => 'Kegiatan/keterangan wajib diisi.',
                ]);

                // NB: submitSesiPartTimeBatch() harus menulis ke tabel
                // `attendances` (dengan shift_id = null), BUKAN ke
                // part_time_schedules -- karena part_time_schedules cuma
                // jadwal referensi rekuren, bukan log presensi harian.
                $this->service->submitSesiPartTimeBatch($employee, $data['sessions'], $data['tanggal']);

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