@extends('owner.dashboard')

@section('title', 'Izin & Cuti')

@section('content')
<div class="crumb">Home <span>›</span> Kehadiran <span>›</span> <b>Izin &amp; Cuti</b></div>
<div class="page-title" style="margin-bottom:18px;">Izin &amp; Cuti</div>

@if (session('success'))
    <div class="badge badge-green" style="display:block; padding:10px 14px; margin-bottom:16px;">
        {{ session('success') }}
    </div>
@endif

{{-- Owner: hanya approval, tanpa form pengajuan (sesuai mockup) --}}
<div class="card">
    <div class="card-head">
        <div>
            <div class="card-title">Approval Izin &amp; Cuti Tim</div>
            <div class="card-sub">Tinjau dan setujui pengajuan seluruh karyawan</div>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <tr><th>Karyawan</th><th>Jenis</th><th>Tanggal</th><th>Durasi</th><th>Lampiran</th><th>Status</th><th>Aksi</th></tr>

            @forelse ($allLeaveRequests as $leave)
                <tr>
                    <td class="row-name">
                        <div class="avatar-dot" style="background:#2E6FDB;">
                            {{ strtoupper(substr($leave->employee->full_name, 0, 1)) }}{{ strtoupper(substr(strrchr($leave->employee->full_name, ' ') ?: '', 1, 1)) }}
                        </div>
                        {{ $leave->employee->full_name }}
                    </td>
                    <td>{{ str_replace('_', ' ', ucfirst($leave->leave_type)) }}</td>
                    <td>
                        {{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M') }}
                        @if ($leave->start_date != $leave->end_date)
                            – {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M') }}
                        @endif
                    </td>
                    <td>{{ $leave->duration_days }} hari</td>
                    <td>
                        @if ($leave->attachment_url)
                            <a href="{{ $leave->attachment_url }}" target="_blank" class="badge badge-gray">📎 lihat</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if ($leave->status === 'menunggu')
                            <span class="badge badge-gold">Menunggu</span>
                        @elseif ($leave->status === 'disetujui')
                            <span class="badge badge-green">Disetujui</span>
                        @else
                            <span class="badge badge-rust">Ditolak</span>
                        @endif
                    </td>
                    <td style="display:flex; gap:6px;">
                        @if ($leave->status === 'menunggu')
                            <form action="{{ route('leave-requests.approve', $leave) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-gold btn-sm">Setujui</button>
                            </form>
                            <form action="{{ route('leave-requests.reject', $leave) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-line btn-sm">Tolak</button>
                            </form>
                        @else
                            <button class="btn btn-line btn-sm" disabled>Selesai</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; color:#9AA0A8;">Belum ada pengajuan izin/cuti dari karyawan.</td></tr>
            @endforelse
        </table>
    </div>
</div>
@endsection