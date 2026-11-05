@extends('karyawan.dashboard')

@section('title', 'Gaji Saya')

@section('content')

<div class="crumb">
    Home <span></span> <b>Gaji Saya</b>
</div>

<div class="page-title" style="margin-bottom:18px;">
    Gaji Saya
</div>


<div class="grid grid-2">

    {{-- ============================================================
    SLIP GAJI (PERIODE TERPILIH)
    ============================================================ --}}

    <div class="card">

        <div class="card-head">

            <div>
                <div class="card-title">
                    Slip Gaji — {{ $periodeLabel }}
                </div>

                <div class="card-sub">
                    {{ $employee->full_name }}
                    ·
                    {{ $employee->employee_code }}
                    ·
                    {{ $employee->employee_type === 'tetap' ? 'Karyawan Tetap' : 'Karyawan Part Time' }}
                </div>
            </div>

            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">

                {{-- DROPDOWN PERIODE --}}
                @if ($riwayat->isNotEmpty())
                    <form method="GET" action="{{ route('payslips.index') }}" style="margin:0;">
                        <select
                            name="periode"
                            onchange="this.form.submit()"
                            style="
                                padding:7px 10px;
                                border:1px solid #E5E7EB;
                                border-radius:8px;
                                font-family:inherit;
                                font-size:12.5px;
                                background:#fff;
                                cursor:pointer;
                            "
                        >
                            @foreach ($riwayat as $r)
                                <option value="{{ $r['key'] }}" @selected($r['key'] === $periodeKey)>
                                    {{ $r['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                {{-- DOWNLOAD PDF --}}
                @if ($payslip)
                    <a
                        href="{{ route('payslips.download-pdf', ['periode' => $periodeKey]) }}"
                        class="btn btn-line btn-sm"
                    >
                        Unduh PDF
                    </a>
                @else
                    <button
                        class="btn btn-line btn-sm"
                        disabled
                        title="Slip gaji belum diterbitkan"
                    >
                        Unduh PDF
                    </button>
                @endif

            </div>

        </div>


        @if ($payslip)

            <div class="divider-label" style="margin-top:0;">
                Pendapatan
            </div>


            {{-- ====================================================
            KARYAWAN PART TIME
            ==================================================== --}}

            @if ($employee->employee_type === 'part_time')

                <div class="pay-row">
                    <div>
                        <div class="pay-label">Fee Mengajar</div>
                    </div>
                    <div class="pay-val pay-pos">
                        Rp {{ number_format($partTime['fee'], 0, ',', '.') }}
                    </div>
                </div>

                @if ($partTime['makan'] > 0)
                    <div class="pay-row">
                        <div>
                            <div class="pay-label">Uang Makan</div>
                        </div>
                        <div class="pay-val pay-pos">
                            Rp {{ number_format($partTime['makan'], 0, ',', '.') }}
                        </div>
                    </div>
                @endif

                @if ($partTime['bensin'] > 0)
                    <div class="pay-row">
                        <div>
                            <div class="pay-label">Uang Bensin</div>
                        </div>
                        <div class="pay-val pay-pos">
                            Rp {{ number_format($partTime['bensin'], 0, ',', '.') }}
                        </div>
                    </div>
                @endif

                <div class="pay-total">
                    <div class="pay-label">Total Diterima</div>
                    <div class="pay-val">
                        Rp {{ number_format($partTime['total'], 0, ',', '.') }}
                    </div>
                </div>

                <div class="note-box" style="margin-top:16px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A6212" stroke-width="2" style="flex-shrink:0; margin-top:1px;">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v5M12 16h.01"/>
                    </svg>
                    <div>
                        <strong>Karyawan Part Time:</strong>
                        Fee mengajar, uang makan, dan uang bensin
                        ditentukan dan diinput secara manual oleh Owner.
                        Uang makan dan uang bensin
                        <strong>tidak dihitung berdasarkan absensi
                        atau jumlah hari hadir.</strong>
                    </div>
                </div>


            {{-- ====================================================
            KARYAWAN TETAP
            ==================================================== --}}

            @else

                @php
                    $bonusKerajinan = (float) ($rincian['bonus_kerajinan'] ?? 0);
                    $bonusKinerja   = (float) ($rincian['bonus_kinerja'] ?? 0);
                    $thrSlip        = (float) ($rincian['thr'] ?? 0);
                    $potonganTelat  = (float) ($rincian['potongan'] ?? 0);
                @endphp

                <div class="pay-row">
                    <div>
                        <div class="pay-label">Gaji Pokok</div>
                        <div class="pay-sub">Gaji bulanan tetap</div>
                    </div>
                    <div class="pay-val pay-pos">
                        Rp {{ number_format($rincian['gaji_pokok'], 0, ',', '.') }}
                    </div>
                </div>

                <div class="pay-row">
                    <div>
                        <div class="pay-label">Uang Makan</div>
                        <div class="pay-sub">
                            Rp {{ number_format($tarifMakan, 0, ',', '.') }}/hari
                            × {{ $payslip->hari_hadir }} hari hadir
                        </div>
                    </div>
                    <div class="pay-val pay-pos">
                        Rp {{ number_format($rincian['uang_makan'], 0, ',', '.') }}
                    </div>
                </div>

                <div class="pay-row">
                    <div>
                        <div class="pay-label">Uang Bensin</div>
                        <div class="pay-sub">
                            Rp {{ number_format($tarifBensin, 0, ',', '.') }}/hari
                            × {{ $payslip->hari_hadir }} hari hadir
                        </div>
                    </div>
                    <div class="pay-val pay-pos">
                        Rp {{ number_format($rincian['uang_bensin'], 0, ',', '.') }}
                    </div>
                </div>

                {{-- Bonus Kerajinan (selalu tampil) --}}
                <div class="pay-row">
                    <div>
                        <div class="pay-label">Bonus Kerajinan</div>
                        <div class="pay-sub">Bonus periode ini</div>
                    </div>
                    <div class="pay-val {{ $bonusKerajinan > 0 ? 'pay-pos' : '' }}"
                         style="{{ $bonusKerajinan > 0 ? '' : 'color:#9AA0A8;' }}">
                        Rp {{ number_format($bonusKerajinan, 0, ',', '.') }}
                    </div>
                </div>

                {{-- Bonus Kinerja (selalu tampil) --}}
                <div class="pay-row">
                    <div>
                        <div class="pay-label">Bonus Kinerja</div>
                        <div class="pay-sub">Bonus periode ini</div>
                    </div>
                    <div class="pay-val {{ $bonusKinerja > 0 ? 'pay-pos' : '' }}"
                         style="{{ $bonusKinerja > 0 ? '' : 'color:#9AA0A8;' }}">
                        Rp {{ number_format($bonusKinerja, 0, ',', '.') }}
                    </div>
                </div>

                {{-- THR (hanya tampil jika ada di slip) --}}
                @if ($thrSlip > 0)
                    <div class="pay-row">
                        <div>
                            <div class="pay-label">THR</div>
                            <div class="pay-sub">Tunjangan Hari Raya</div>
                        </div>
                        <div class="pay-val pay-pos">
                            Rp {{ number_format($thrSlip, 0, ',', '.') }}
                        </div>
                    </div>
                @endif

                {{-- Potongan Telat (selalu tampil) --}}
                <div class="divider-label">Potongan</div>

                <div class="pay-row">
                    <div>
                        <div class="pay-label">Potongan Telat</div>
                        <div class="pay-sub">Total potongan keterlambatan periode ini</div>
                    </div>
                    @if ($potonganTelat > 0)
                        <div class="pay-val pay-neg">
                            − Rp {{ number_format($potonganTelat, 0, ',', '.') }}
                        </div>
                    @else
                        <div class="pay-val" style="color:#9AA0A8;">
                            Rp 0
                        </div>
                    @endif
                </div>

                <div class="pay-total">
                    <div class="pay-label">Total Diterima</div>
                    <div class="pay-val">
                        Rp {{ number_format($payslip->total_diterima, 0, ',', '.') }}
                    </div>
                </div>

                <div class="note-box" style="margin-top:16px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A6212" stroke-width="2" style="flex-shrink:0; margin-top:1px;">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v5M12 16h.01"/>
                    </svg>
                    <div>
                        <strong>Tanggal merah / hari libur nasional:</strong>
                        gaji pokok tetap dibayar penuh,
                        namun uang makan &amp; uang bensin
                        tidak diberikan karena tidak ada kehadiran.
                    </div>
                </div>

            @endif

        @else

            <div class="field-hint" style="text-align:center; padding:40px 0;">
                Belum ada slip gaji yang diterbitkan Owner.
            </div>

        @endif

    </div>



    {{-- ============================================================
    THR
    ============================================================ --}}

    <div class="card">

        <div class="card-title" style="margin-bottom:16px;">
            Tunjangan Hari Raya (THR)
        </div>

        <div class="note-box" style="margin-top:0;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A6212" stroke-width="2" style="flex-shrink:0; margin-top:1px;">
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 8v5M12 16h.01"/>
            </svg>
            <div>
                THR mulai berlaku pada
                <strong>tahun kedua masa kerja</strong>.
                Bergabung
                {{ \Carbon\Carbon::parse($employee->join_date)->translatedFormat('M Y') }}.
            </div>
        </div>

        <div class="kv">
            <span class="kv-lbl">Status kelayakan</span>
            <span class="kv-val" style="color: {{ $thrEligible ? 'var(--green)' : 'var(--rust)' }};">
                {{ $thrEligible ? 'Memenuhi syarat' : 'Belum memenuhi syarat' }}
            </span>
        </div>

        <div class="kv">
            <span class="kv-lbl">Masa kerja saat ini</span>
            <span class="kv-val">
                {{ $masaKerjaTahun }} tahun {{ $masaKerjaBulan }} bulan
            </span>
        </div>

        @if ($thrEligible)
            <div class="kv">
                <span class="kv-lbl">Estimasi THR (1× gaji pokok)</span>
                <span class="kv-val mono">
                    Rp {{ number_format($thrEstimasi, 0, ',', '.') }}
                </span>
            </div>
        @endif


        {{-- REKAP KEHADIRAN (mengikuti periode slip yang dipilih) --}}

        @if ($payslip && $employee->employee_type !== 'part_time')

            <div class="divider-label">
                Rekap Kehadiran — {{ $periodeLabel }}
            </div>

            <div class="field-hint" style="margin-bottom:10px;">
                Uang makan &amp; uang bensin dihitung
                dari jumlah hari absen yang tervalidasi
                radius kerja dengan foto real-time.
            </div>

            <div class="kv">
                <span class="kv-lbl">Absen valid dalam radius</span>
                <span class="kv-val" style="color:var(--green);">
                    {{ $payslip->hari_hadir }} hari
                </span>
            </div>

            @php
                $hariAcuan = ($payslip->hari_efektif ?? 0) > 0 ? $payslip->hari_efektif : 23;

                $persenHadir = $payslip->hari_hadir > 0
                    ? min(100, round(($payslip->hari_hadir / $hariAcuan) * 100))
                    : 0;
            @endphp

            <div class="progress">
                <div class="progress-fill" style="width:{{ $persenHadir }}%;"></div>
            </div>

        @endif

        <div class="field-hint" style="margin-top:14px;">
            Seluruh komponen gaji di atas ditetapkan
            oleh Owner/HR dan tidak dapat diubah sendiri.
        </div>

    </div>

</div>



{{-- ================================================================
RIWAYAT GAJI
================================================================ --}}

<div class="card" style="margin-top:18px;">

    <div class="card-title" style="margin-bottom:14px;">
        Riwayat Gaji
    </div>

    @if ($riwayat->isEmpty())

        <div class="field-hint" style="text-align:center; padding:24px 0;">
            Belum ada riwayat slip gaji.
        </div>

    @else

        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="text-align:left; color:#9AA0A8; font-size:11px; text-transform:uppercase;">
                        <th style="padding:8px 6px; border-bottom:1px solid #EDEEF0;">Periode</th>
                        <th style="padding:8px 6px; border-bottom:1px solid #EDEEF0;">Diterbitkan</th>
                        <th style="padding:8px 6px; border-bottom:1px solid #EDEEF0; text-align:right;">Total Diterima</th>
                        <th style="padding:8px 6px; border-bottom:1px solid #EDEEF0; text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($riwayat as $r)
                        <tr style="{{ $r['key'] === $periodeKey ? 'background:#FFF8EA;' : '' }}">
                            <td style="padding:10px 6px; border-bottom:1px solid #F4F5F2; font-weight:600;">
                                {{ $r['label'] }}
                            </td>
                            <td style="padding:10px 6px; border-bottom:1px solid #F4F5F2; color:#6B7280;">
                                {{ $r['published_at']?->translatedFormat('d M Y') ?? '-' }}
                            </td>
                            <td style="padding:10px 6px; border-bottom:1px solid #F4F5F2; text-align:right; font-weight:600;">
                                Rp {{ number_format($r['total'], 0, ',', '.') }}
                            </td>
                            <td style="padding:10px 6px; border-bottom:1px solid #F4F5F2; text-align:right; white-space:nowrap;">
                                <a href="{{ route('payslips.index', ['periode' => $r['key']]) }}" class="btn btn-line btn-sm">
                                    Lihat
                                </a>
                                <a href="{{ route('payslips.download-pdf', ['periode' => $r['key']]) }}" class="btn btn-line btn-sm">
                                    PDF
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    @endif

</div>

@endsection