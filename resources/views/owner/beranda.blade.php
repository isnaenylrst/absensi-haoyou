@extends('owner.dashboard')

@section('title', 'Beranda')

@section('content')
<div class="crumb">Home <span>›</span> <b>Beranda</b></div>

<div class="page-head">
    <div>
        <div class="page-title">Selamat pagi, {{ auth()->user()->employee->full_name }} 👋</div>
        <div class="card-sub">{{ $tanggalHariIni }}</div>
    </div>
    <div class="page-actions">
        <div class="badge badge-green" style="padding:8px 14px; font-size:11.5px;">● Lokasi aktif</div>
    </div>
</div>

<div class="grid grid-4">
    <div class="card stat-card">
        <div class="stat-top">
            <div class="stat-ico" style="background:var(--blue-soft);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2"><circle cx="9" cy="8" r="3.2"/><path d="M2.7 19c1.2-3.6 3.4-5.4 6.3-5.4s5.1 1.8 6.3 5.4"/><circle cx="17.5" cy="7.5" r="2.6"/><path d="M15.5 13.3c2.2.2 3.8 1.7 4.8 4.7"/></svg>
            </div>
            <span class="badge badge-gray">Aktif</span>
        </div>
        <div class="stat-value mono">{{ $totalKaryawan }}</div>
        <div class="stat-label">Total Karyawan</div>
    </div>

    <div class="card stat-card">
        <div class="stat-top">
            <div class="stat-ico" style="background:var(--green-soft);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
            </div>
            <span class="badge badge-green">{{ $totalKaryawan > 0 ? round(($absenHariIni / $totalKaryawan) * 100) : 0 }}%</span>
        </div>
        <div class="stat-value mono">{{ $absenHariIni }}/{{ $totalKaryawan }}</div>
        <div class="stat-label">Karyawan Absen Hari Ini</div>
    </div>

    <div class="card stat-card">
        <div class="stat-top">
            <div class="stat-ico" style="background:var(--gold-pale);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--gold-deep)" stroke-width="2"><path d="M12 21s7-6.2 7-11.5A7 7 0 1 0 5 9.5C5 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.3"/></svg>
            </div>
            <span class="badge badge-gold">Hari ini</span>
        </div>
        <div class="stat-value mono">{{ $kunjunganHariIni }}</div>
        <div class="stat-label">Karyawan Kunjungan Klien</div>
    </div>

    <div class="card stat-card">
        <div class="stat-top">
            <div class="stat-ico" style="background:var(--rust-soft);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--rust)" stroke-width="2"><rect x="3.5" y="4" width="17" height="16" rx="2"/><path d="M8 2.5v3M16 2.5v3M3.5 9.5h17"/></svg>
            </div>
            <span class="badge badge-rust">{{ $izinMenunggu > 0 ? 'Perlu ditinjau' : 'Aman' }}</span>
        </div>
        <div class="stat-value mono">{{ $izinMenunggu }}</div>
        <div class="stat-label">Izin &amp; Cuti Menunggu</div>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <div class="card-head">
        <div>
            <div class="card-title">Aktivitas Terbaru Tim</div>
            <div class="card-sub">Ringkasan aktivitas seluruh karyawan hari ini</div>
        </div>
    </div>

    @forelse ($aktivitasTim as $item)
        <div class="timeline-item">
            <div class="timeline-dot" style="background:{{ $item['dot'] }};"></div>
            <div style="flex:1;">
                <div class="ti-row">
                    <div>
                        <div class="timeline-title">{{ $item['title'] }}</div>
                        <div class="timeline-sub">{{ $item['sub'] }}</div>
                    </div>
                    <div class="timeline-time">{{ $item['time'] }}</div>
                </div>
            </div>
        </div>
    @empty
        <div class="field-hint" style="text-align:center; padding:30px 0;">Belum ada aktivitas tercatat hari ini.</div>
    @endforelse
</div>
@endsection