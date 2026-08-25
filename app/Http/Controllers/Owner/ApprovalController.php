<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Branch;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['q', 'tanggal', 'branch_id', 'employee_type', 'status']);

        $attendances = Attendance::query()
            ->with(['employee.branch', 'shift', 'partTimeSchedule'])
            ->when($filters['tanggal'] ?? null, function ($query, $tanggal) {
                $query->whereDate('tanggal', $tanggal);
            }, function ($query) {
                $query->whereDate('tanggal', now()->toDateString());
            })
            ->when($filters['branch_id'] ?? null, function ($query, $branchId) {
                $query->where('branch_id', $branchId);
            })
            ->when($filters['employee_type'] ?? null, function ($query, $type) {
                $query->whereHas('employee', function ($q) use ($type) {
                    $q->where('employee_type', $type);
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('tanggal')
            ->orderByDesc('check_in')
            ->paginate(15)
            ->withQueryString();

        $attendances->getCollection()->transform(function ($attendance) {
            $attendance->late_label = 'Tepat waktu';
            $attendance->late_minutes = 0;

            if (!$attendance->check_in) {
                return $attendance;
            }

            $startTime = $attendance->shift?->start_time
                ?? $attendance->partTimeSchedule?->start_time;

            if (!$startTime) {
                return $attendance;
            }

            $date = $attendance->tanggal instanceof Carbon
                ? $attendance->tanggal->format('Y-m-d')
                : Carbon::parse($attendance->tanggal)->format('Y-m-d');

            $scheduledTime = Carbon::parse($date . ' ' . $startTime);
            $checkInTime = Carbon::parse($attendance->check_in);

            if ($checkInTime->greaterThan($scheduledTime)) {
                $minutes = $scheduledTime->diffInMinutes($checkInTime);

                $attendance->late_minutes = $minutes;

                $hours = intdiv($minutes, 60);
                $remainingMinutes = $minutes % 60;

                $parts = [];

                if ($hours > 0) {
                    $parts[] = $hours . ' jam';
                }

                if ($remainingMinutes > 0) {
                    $parts[] = $remainingMinutes . ' menit';
                }

                $attendance->late_label = 'Terlambat ' . implode(' ', $parts);
            }

            return $attendance;
        });

        $branches = Branch::orderBy('name')->get();

        return view('owner.approval', [
            'attendances' => $attendances,
            'branches' => $branches,
            'filters' => $filters,
        ]);
    }
}