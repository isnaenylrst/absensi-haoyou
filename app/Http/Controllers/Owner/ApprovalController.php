<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Branch;
use Illuminate\Http\Request;

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

        $branches = Branch::orderBy('name')->get();

        return view('owner.approval', [
            'attendances' => $attendances,
            'branches' => $branches,
            'filters' => $filters,
        ]);
    }
}