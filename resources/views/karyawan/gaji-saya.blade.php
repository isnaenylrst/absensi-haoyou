@extends('karyawan.dashboard')

@section('title', 'Gaji Saya')

@section('content')
<div class="crumb">Home <span></span> <b>Gaji Saya</b></div>
<div class="page-title" style="margin-bottom:18px;">Gaji Saya</div>

<div class="grid grid-2">
    {{-- ============================================================ --}}
    {{-- Slip Gaji Bulan Berjalan --}}
    {{-- ============================================================ --}}
    <div class="card">
        <div class="card-head">
            <div>
                <div class="card-title">Slip Gaji — {{ $periodeLabel }}</div>
                <div class="card-sub">
                    {{ $employee->full_name }} · {{ $employee->employee_code }} ·
                    {{ $employee->employee_type === 'tetap' ? 'Karyawan Tetap' : 'Karyawan Part Time' }}
                </div>
            </div>
           @if ($payslip)
                <a href="{{ route('payslips.download-pdf') }}" class="btn btn-line btn-sm">Unduh PDF</a>
            @else
                <button class="btn btn-line btn-sm" disabled title="Slip gaji belum diterbitkan">Unduh PDF</button>
            @endif
        </div>

        @if ($payslip && $rincian)
            <div class="divider-label" style="margin-top:0;">Pendapatan</div>

            <div class="pay-row">
                <div>
                    <div class="pay-label">Gaji Pokok</div>
                    <div class="pay-sub">Gaji bulanan tetap</div>
                </div>
                <div class="pay-val pay-pos">Rp {{ number_format($rincian['gaji_pokok'], 0, ',', '.') }}</div>
            </div>

            <div class="pay-row">
                <div>
                    <div class="pay-label">Uang Makan</div>
                    <div class="pay-sub">Rp {{ number_format($payrollComponent->meal_rate, 0, ',', '.') }}/hari × {{ $payslip->hari_hadir }} hari hadir</div>
                </div>
                <div class="pay-val pay-pos">Rp {{ number_format($rincian['uang_makan'], 0, ',', '.') }}</div>
            </div>

            <div class="pay-row">
                <div>
                    <div class="pay-label">Uang Bensin</div>
                    <div class="pay-sub">Rp {{ number_format($payrollComponent->transport_rate, 0, ',', '.') }}/hari × {{ $payslip->hari_hadir }} hari hadir</div>
                </div>
                <div class="pay-val pay-pos">Rp {{ number_format($rincian['uang_bensin'], 0, ',', '.') }}</div>
            </div>

            @if ($rincian['tunjangan'] > 0)
                <div class="pay-row">
                    <div>
                        <div class="pay-label">Tunjangan Kehadiran</div>
                        <div class="pay-sub">Bonus tanpa alpa &amp; tanpa telat &gt; 3x</div>
                    </div>
                    <div class="pay-val pay-pos">Rp {{ number_format($rincian['tunjangan'], 0, ',', '.') }}</div>
                </div>
            @endif

            @if ($rincian['potongan'] > 0)
                <div class="divider-label">Potongan</div>
                <div class="pay-row">
                    <div>
                        <div class="pay-label">Potongan Keterlambatan</div>
                        <div class="pay-sub">Total potongan bulan ini</div>
                    </div>
                    <div class="pay-val pay-neg">− Rp {{ number_format($rincian['potongan'], 0, ',', '.') }}</div>
                </div>
            @endif

            <div class="pay-total">
                <div class="pay-label">Total Diterima</div>
                <div class="pay-val">Rp {{ number_format($payslip->total_diterima, 0, ',', '.') }}</div>
            </div>

            <div class="note-box" style="margin-top:16px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A6212" stroke-width="2" style="flex-shrink:0; margin-top:1px;"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                <div><strong>Tanggal merah / hari libur nasional:</strong> gaji pokok tetap dibayar penuh, namun uang makan &amp; uang bensin tidak diberikan karena tidak ada kehadiran. Slip gaji hanya bisa diunduh di periode bulanan setelah diterbitkan.</div>
            </div>
        @else
            <div class="field-hint" style="text-align:center; padding:40px 0;">
                Slip gaji bulan {{ $periodeLabel }} belum diterbitkan Owner.
            </div>
        @endif
    </div>

    {{-- ============================================================ --}}
    {{-- THR --}}
    {{-- ============================================================ --}}
    <div class="card">
        <div class="card-title" style="margin-bottom:16px;">Tunjangan Hari Raya (THR)</div>

        <div class="note-box" style="margin-top:0;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A6212" stroke-width="2" style="flex-shrink:0; margin-top:1px;"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
            <div>THR mulai berlaku pada <strong>tahun kedua masa kerja</strong>. Bergabung {{ \Carbon\Carbon::parse($employee->join_date)->translatedFormat('M Y') }}.</div>
        </div>

        <div class="kv">
            <span class="kv-lbl">Status kelayakan</span>
            <span class="kv-val" style="color: {{ $thrEligible ? 'var(--green)' : 'var(--rust)' }};">
                {{ $thrEligible ? 'Memenuhi syarat' : 'Belum memenuhi syarat' }}
            </span>
        </div>
        <div class="kv">
            <span class="kv-lbl">Masa kerja saat ini</span>
            <span class="kv-val">{{ $masaKerjaTahun }} tahun {{ $masaKerjaBulan }} bulan</span>
        </div>
        @if ($thrEligible)
            <div class="kv">
                <span class="kv-lbl">Estimasi THR (1× gaji pokok)</span>
                <span class="kv-val mono">Rp {{ number_format($thrEstimasi, 0, ',', '.') }}</span>
            </div>
        @endif

        @if ($payslip)
            <div class="divider-label">Rekap Kehadiran Bulan Ini</div>
            <div class="field-hint" style="margin-bottom:10px;">
                Uang makan &amp; uang bensin dihitung dari jumlah hari absen yang tervalidasi radius kerja dengan foto real-time.
            </div>
            <div class="kv">
                <span class="kv-lbl">Absen valid dalam radius</span>
                <span class="kv-val" style="color:var(--green);">{{ $payslip->hari_hadir }} hari</span>
            </div>
            @php
                $persenHadir = $payslip->hari_hadir > 0 ? min(100, round(($payslip->hari_hadir / 23) * 100)) : 0;
            @endphp
            <div class="progress"><div class="progress-fill" style="width:{{ $persenHadir }}%;"></div></div>
        @endif

        <div class="field-hint" style="margin-top:14px;">
            Seluruh komponen gaji di atas ditetapkan oleh Owner/HR dan tidak dapat diubah sendiri.
        </div>
    </div>
</div>
@endsection