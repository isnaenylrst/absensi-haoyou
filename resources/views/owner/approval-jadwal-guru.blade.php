@extends('owner.dashboard')

@section('title', 'Approval Jadwal Guru | Haoyou Educator')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/owner/jadwal-kerja.css') }}">
    <link rel="stylesheet" href="{{ asset('/css/owner/approval-presensi.css') }}">
@endpush

@section('content')
<div class="crumb"><a href="{{ route('approval') }}"><b>Approval Presensi</b></a> / Approval Jadwal Guru</div>
<div class="page-head"><div class="page-title">Approval Jadwal Guru</div></div>

@if (session('success'))
    <div class="note-box">{{ session('success') }}</div>
@endif

<form method="GET" class="toolbar">
    <div class="toolbar-left">
        <div class="search-box"><input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama guru..."></div>
        <select name="status" class="field-input-inline" onchange="this.form.submit()">
            <option value="pending" @selected($status === 'pending')>Pending</option>
            <option value="all" @selected($status === 'all')>Semua Status</option>
            <option value="approved" @selected($status === 'approved')>Approved</option>
            <option value="rejected" @selected($status === 'rejected')>Rejected</option>
        </select>
    </div>
</form>

<div class="card">
    <div class="table-wrap">
        <table>
            <tr><th>Guru</th><th>Jenis</th><th>Hari / Tanggal</th><th>Periode</th><th>Jam</th><th>Aktivitas / Alasan</th><th>Status</th><th>Aksi</th></tr>
            @forelse($requests as $item)
                <tr>
                    <td>{{ $item->employee->full_name }}</td>
                    <td>{{ ucfirst($item->schedule_type) }}</td>
                    <td>{{ $item->scope === 'single_day' ? $item->tanggal?->translatedFormat('d M Y') : ucfirst($item->day_of_week) }}</td>
                    <td>{{ $item->valid_from?->format('d M Y') }} - {{ $item->valid_until?->format('d M Y') }}</td>
                    <td class="mono">{{ substr($item->start_time, 0, 5) }} - {{ substr($item->end_time, 0, 5) }}</td>
                    <td>{{ $item->schedule_type === 'absence' ? $item->reason : $item->activity }}</td>
                    <td><span class="badge badge-{{ $item->status === 'approved' ? 'green' : ($item->status === 'rejected' ? 'rust' : 'gold') }}">{{ ucfirst($item->status) }}</span></td>
                    <td>
                        @if($item->status === 'pending')
                            <div class="approval-action-buttons">
                                <form method="POST" action="{{ route('approval.jadwal-guru.update', $item) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button class="btn btn-gold btn-xs">Approve</button></form>
                                <form method="POST" action="{{ route('approval.jadwal-guru.update', $item) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button class="btn btn-line btn-xs">Reject</button></form>
                            </div>
                        @else
                            <span class="field-hint">{{ $item->approved_at?->format('d/m/Y H:i') ?? '-' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty-state">Tidak ada pengajuan jadwal guru.</td></tr>
            @endforelse
        </table>
    </div>
    {{ $requests->links() }}
</div>
@endsection
