<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ClientVisit;
use Illuminate\Http\Request;

class KunjunganController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['q', 'tanggal', 'branch_id', 'visit_type', 'review_status']);

        $visits = ClientVisit::query()
            ->with('employee.branch')
            ->when($filters['tanggal'] ?? null, function ($query, $tanggal) {
                $query->whereDate('visited_at', $tanggal);
            })
            ->when($filters['branch_id'] ?? null, function ($query, $branchId) {
                $query->whereHas('employee', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            })
            ->when($filters['visit_type'] ?? null, function ($query, $type) {
                $query->where('visit_type', $type);
            })
            ->when($filters['review_status'] ?? null, function ($query, $status) {
                $query->where('review_status', $status);
            })
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('client_name', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($qe) use ($search) {
                            $qe->where('full_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('visited_at')
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::orderBy('name')->get();

        $visitTypes = ClientVisit::query()
            ->select('visit_type')
            ->distinct()
            ->orderBy('visit_type')
            ->pluck('visit_type');

        return view('owner.kunjungan', [
            'visits' => $visits,
            'branches' => $branches,
            'visitTypes' => $visitTypes,
            'filters' => $filters,
        ]);
    }

    public function updateStatus(Request $request, ClientVisit $visit)
    {
        $request->validate([
            'review_status' => 'required|in:wajar,perlu_ditinjau',
        ]);

        $visit->update(['review_status' => $request->review_status]);

        return response()->json(['success' => true]);
    }
}