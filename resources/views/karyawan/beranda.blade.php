@extends('karyawan.dashboard')

@section('title', 'Beranda')

@section('content')

<div class="crumb">
    Home <span>›</span> <b>Beranda</b>
</div>

<div class="page-head">
    <div>
        <div class="page-title">
            Selamat {{ $sapaan }}, {{ $employee->full_name }} 👋
        </div>

        <div class="card-sub">
            {{ $tanggalHariIni }}
        </div>
    </div>

    <div class="page-actions">
        <div
            class="badge badge-green"
            style="padding:8px 14px; font-size:11.5px;"
        >
            ● Lokasi aktif
        </div>
    </div>
</div>


{{-- =========================================================
     TOMBOL PRESENSI
     ========================================================= --}}

@if (!$absenMasuk)

    {{-- BELUM ABSEN MASUK --}}
    <div class="page-actions" style="margin-bottom:18px;">

        <a
            href="{{ Route::has('presensi') ? route('presensi') : '#' }}"
            class="btn"
            style="
                display:inline-flex;
                align-items:center;
                gap:8px;
                background:#f9a825;
                color:#fff;
                border:none;
                border-radius:10px;
                padding:12px 24px;
                font-size:14px;
                font-weight:700;
                text-decoration:none;
                box-shadow:0 5px 12px rgba(249,168,37,0.20);
            "
        >
            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path d="M20 6 9 17l-5-5"/>
            </svg>

            Presensi Masuk
        </a>

    </div>

@elseif (!$absenMasuk->check_out)

    {{-- SUDAH ABSEN MASUK, BELUM ABSEN PULANG --}}
    <div class="page-actions" style="margin-bottom:18px;">

        <a
            href="{{ Route::has('presensi') ? route('presensi') : '#' }}"
            class="btn"
            style="
                display:inline-flex;
                align-items:center;
                gap:8px;
                background:#f9a825;
                color:#fff;
                border:none;
                border-radius:10px;
                padding:12px 24px;
                font-size:14px;
                font-weight:700;
                text-decoration:none;
                box-shadow:0 5px 12px rgba(249,168,37,0.20);
            "
        >
            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path d="M12 5v14"/>
                <path d="M5 12h14"/>
            </svg>

            Presensi Pulang
        </a>

    </div>

@else

    {{-- SUDAH ABSEN MASUK DAN PULANG --}}
    <div class="page-actions" style="margin-bottom:18px;">

        <button
            type="button"
            disabled
            class="btn"
            style="
                display:inline-flex;
                align-items:center;
                gap:8px;
                background:#e8f5e9;
                color:#2e8b57;
                border:none;
                border-radius:10px;
                padding:12px 24px;
                font-size:14px;
                font-weight:700;
                cursor:default;
            "
        >
            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path d="M20 6 9 17l-5-5"/>
            </svg>

            Presensi Selesai
        </button>

    </div>

@endif


{{-- =========================================================
     STATISTIK PRESENSI
     ========================================================= --}}

<div class="grid grid-3">

    {{-- ================= JAM MASUK ================= --}}
    <div class="card stat-card">

        <div class="stat-top">

            <div
                class="stat-ico"
                style="
                    background:
                    {{ $absenMasuk
                        ? ($absenMasuk->status === 'tepat_waktu'
                            ? 'var(--green-soft)'
                            : 'var(--rust-soft)')
                        : 'var(--line-soft)' }};
                "
            >
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="{{ $absenMasuk
                        ? ($absenMasuk->status === 'tepat_waktu'
                            ? 'var(--green)'
                            : 'var(--rust)')
                        : 'var(--text-faint)' }}"
                    stroke-width="2"
                >
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
            </div>


            @if ($absenMasuk)

                <span
                    class="badge
                    {{ $absenMasuk->status === 'tepat_waktu'
                        ? 'badge-green'
                        : 'badge-rust' }}"
                >
                    {{
                        $absenMasuk->status === 'tepat_waktu'
                            ? 'Tepat waktu'
                            : 'Terlambat '.$absenMasuk->late_minutes.'m'
                    }}
                </span>

            @else

                <span class="badge badge-gray">
                    Belum presensi
                </span>

            @endif

        </div>


        <div class="stat-value mono">

            {{
                $absenMasuk
                    ? \Carbon\Carbon::parse($absenMasuk->check_in)->format('H:i')
                    : '--:--'
            }}

        </div>


        <div class="stat-label">

            Jam masuk

            @if ($absenMasuk && $employee->employee_type === 'tetap')

                — {{ $absenMasuk->shiftSchedule?->shift?->name ?? 'Shift' }}

            @endif

        </div>

    </div>


    {{-- ================= RADIUS ================= --}}
    <div class="card stat-card">

        <div class="stat-top">

            <div
                class="stat-ico"
                style="background:var(--gold-pale);"
            >
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="var(--gold-deep)"
                    stroke-width="2"
                >
                    <path d="M12 21s7-6.2 7-11.5A7 7 0 1 0 5 9.5C5 14.8 12 21 12 21Z"/>
                    <circle cx="12" cy="9.5" r="2.3"/>
                </svg>
            </div>


            @if ($absenMasuk)

                <span class="badge badge-gold">
                    ±{{ number_format($absenMasuk->distance_m, 0) }} m
                </span>

            @endif

        </div>


        <div class="stat-value mono">

            {{
                $absenMasuk
                    ? number_format($absenMasuk->distance_m, 0).' m'
                    : '—'
            }}

        </div>


        <div class="stat-label">
            Radius dari titik kantor
        </div>

    </div>


    {{-- ================= KETERLAMBATAN ================= --}}
    <div class="card stat-card">

        <div class="stat-top">

            <div
                class="stat-ico"
                style="background:var(--rust-soft);"
            >
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="var(--rust)"
                    stroke-width="2"
                >
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 8v4l2.5 2.5"/>
                </svg>
            </div>


            @if ($potonganTelat > 0)

                <span class="badge badge-rust">
                    -Rp {{ number_format($potonganTelat, 0, ',', '.') }}
                </span>

            @endif

        </div>


        <div class="stat-value mono">
            {{ $jumlahTelat }}x
        </div>


        <div class="stat-label">
            Keterlambatan bulan ini
        </div>

    </div>

</div>


{{-- =========================================================
     AKTIVITAS HARI INI
     ========================================================= --}}

<div
    class="card"
    style="margin-top:16px;"
>

    <div class="card-head">

        <div>

            <div class="card-title">
                Aktivitas Hari Ini
            </div>

            <div class="card-sub">
                Riwayat absensi &amp; kunjungan real-time
            </div>

        </div>

    </div>


    @forelse ($aktivitas as $item)

        <div class="timeline-item">

            <div
                class="timeline-dot"
                style="background:{{ $item['dot'] }};"
            ></div>


            <div style="flex:1;">

                <div class="ti-row">

                    <div>

                        <div class="timeline-title">
                            {{ $item['title'] }}
                        </div>

                        <div class="timeline-sub">
                            {{ $item['sub'] }}
                        </div>

                    </div>


                    <div class="timeline-time">
                        {{ $item['time'] }}
                    </div>

                </div>

            </div>

        </div>

    @empty

        <div
            class="field-hint"
            style="
                text-align:center;
                padding:30px 0;
            "
        >
            Belum ada aktivitas tercatat hari ini.
        </div>

    @endforelse

</div>

@endsection