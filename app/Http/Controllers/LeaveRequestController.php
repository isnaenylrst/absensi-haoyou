<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequestRequest;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    /**
     * Halaman Izin & Cuti.
     * - Karyawan: lihat form ajukan izin + riwayat pengajuan miliknya sendiri.
     * - Owner: sama seperti karyawan (owner juga karyawan/EMP0001), DITAMBAH
     *   card "Approval Izin & Cuti Tim" berisi semua pengajuan seluruh
     *   karyawan yang bisa disetujui/ditolak.
     */
    public function index(): View
{
    /** @var \App\Models\User $user */
    $user = auth()->user();

    $myLeaveRequests = LeaveRequest::where('employee_id', $user->employee_id)
        ->latest('created_at')
        ->get();

    if ($user->role === 'owner') {
        $allLeaveRequests = LeaveRequest::with('employee')
            ->orderByRaw("FIELD(status, 'menunggu', 'disetujui', 'ditolak')")
            ->latest('created_at')
            ->get();

        return view('owner.izin-cuti', [
            'myLeaveRequests' => $myLeaveRequests,
            'allLeaveRequests' => $allLeaveRequests,
        ]);
    }

    return view('karyawan.izin-cuti', [
        'myLeaveRequests' => $myLeaveRequests,
    ]);
}

    /**
     * Karyawan/owner mengajukan izin baru untuk dirinya sendiri.
     */
    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        $endDate = \Carbon\Carbon::parse($validated['end_date']);

        $attachmentUrl = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('lampiran-izin', 'public');
            $attachmentUrl = Storage::url($path);
        }

        LeaveRequest::create([
            'employee_id' => auth()->user()->employee_id,
            'leave_type' => $validated['leave_type'],
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'duration_days' => $startDate->diffInDays($endDate) + 1, // inklusif kedua tanggal
            'reason' => $validated['reason'] ?? null,
            'attachment_url' => $attachmentUrl,
            'status' => 'menunggu',
        ]);

        return back()->with('success', 'Pengajuan izin berhasil dikirim, menunggu persetujuan.');
    }

    /**
     * Owner menyetujui pengajuan izin.
     */
    public function approve(LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless(auth()->user()->role === 'owner', 403, 'Hanya owner yang bisa menyetujui izin.');

        $leaveRequest->update([
            'status' => 'disetujui',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', "Izin {$leaveRequest->employee->full_name} disetujui.");
    }

    /**
     * Owner menolak pengajuan izin.
     */
    public function reject(LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless(auth()->user()->role === 'owner', 403, 'Hanya owner yang bisa menolak izin.');

        $leaveRequest->update([
            'status' => 'ditolak',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', "Izin {$leaveRequest->employee->full_name} ditolak.");
    }
}