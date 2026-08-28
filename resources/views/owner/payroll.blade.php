@extends('owner.dashboard')

@section('title', 'Payroll')

@section('content')
<div class="crumb">Home <span>›</span> <b>Payroll</b></div>
<div class="page-title" style="margin-bottom:18px;">Payroll</div>
<form method="GET" action="{{ route('payroll.index') }}"
      id="periode-form"
      style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
    <label style="font-size:12px; font-weight:700; color:#666;">Periode:</label>

    @php
        $bulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        [$selectedYear, $selectedMonth] = explode('-', $periodeValue);
        $selectedMonth = (int) $selectedMonth;
        $selectedYear = (int) $selectedYear;
        $tahunMulai = now()->year - 3;
        $tahunSelesai = now()->year + 1;
    @endphp

    <select id="periode-bulan" onchange="document.getElementById('periode-hidden').value = document.getElementById('periode-tahun').value + '-' + document.getElementById('periode-bulan').value; document.getElementById('periode-form').submit();"
            style="padding:6px 10px; border:1px solid #ddd; border-radius:6px; font-size:12px; font-weight:700; color:#666; font-family:inherit;">
             @foreach ($bulanList as $num => $label)
            <option value="{{ str_pad($num, 2, '0', STR_PAD_LEFT) }}" {{ $num === $selectedMonth ? 'selected' : '' }}>
            {{ $label }}
            </option>
        @endforeach
    </select>

    <select id="periode-tahun" onchange="document.getElementById('periode-hidden').value = document.getElementById('periode-tahun').value + '-' + document.getElementById('periode-bulan').value; document.getElementById('periode-form').submit();"
            style="padding:6px 10px; border:1px solid #ddd; border-radius:6px; font-size:12px; font-weight:700; color:#666; font-family:inherit;">
            @for ($y = $tahunSelesai; $y >= $tahunMulai; $y--)
            <option value="{{ $y }}" {{ $y === $selectedYear ? 'selected' : '' }}>{{ $y }}</option>
             @endfor
    </select>

    <input type="hidden" id="periode-hidden" name="periode" value="{{ $periodeValue }}">
    <a href="{{ route('payroll.history') }}" class="btn btn-line btn-sm" style="margin-left:auto;">
        Riwayat Payroll
    </a>
</form>

@unless ($isCurrentPeriode)
    <div class="owner-panel-note" style="background:#FFF6E5; border-color:#E9C46A;">
        Anda sedang melihat periode <strong>{{ $periodeLabel }}</strong> (bukan bulan berjalan).
        Perubahan pada kolom Gaji Pokok / Uang Makan / Uang Bensin / Bonus akan tersimpan sebagai
        <strong>rate terkini</strong> dan berlaku untuk perhitungan ke depan — bukan hanya untuk periode ini.
        Untuk melihat slip yang sudah pernah diterbitkan, buka <a href="{{ route('payroll.history') }}">Riwayat Payroll</a>.
    </div>
@endunless

@if (session('success'))
    <div class="badge badge-green" style="display:block; padding:10px 14px; margin-bottom:16px;">
        {{ session('success') }}
    </div>
@endif

<div class="owner-panel-note">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#215A3D" stroke-width="2" style="flex-shrink:0; margin-top:1px;"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
    <div>
        Ubah nilai lalu klik <strong>Simpan</strong> per baris untuk update komponen gaji.
        <strong>Gaji Pokok dihitung bulanan</strong>, sedangkan <strong>Uang Makan &amp; Uang Bensin dihitung harian</strong> (rate × hari hadir aktual bulan {{ $periodeLabel }}).
        Kolom Hari Hadir/Total Jam &amp; Potongan Telat dihitung otomatis dari data presensi, tidak bisa diketik manual.
    </div>
</div>

{{-- ============================================================ --}}
{{-- Karyawan Tetap - Gaji Bulanan --}}
{{-- ============================================================ --}}
<div class="divider-label" style="margin-top:0;">Karyawan Tetap — Gaji Bulanan</div>
<div class="table-wrap">
    <table class="paytable">
        <tr>
            <th>Karyawan</th>
            <th>Gaji Pokok<br><span style="font-weight:600; text-transform:none; font-size:10px;">/bulan</span></th>
            <th>Uang Makan<br><span style="font-weight:600; text-transform:none; font-size:10px;">rate/hari</span></th>
            <th>Uang Bensin<br><span style="font-weight:600; text-transform:none; font-size:10px;">rate/hari</span></th>
            <th>Hari Hadir</th>
            <th>Bonus</th>
            <th>Potongan Telat</th>
            <th>THR Aktif</th>
            <th>Total</th>
            <th></th>
        </tr>

        @forelse ($tetapEmployees as $employee)
            @php
                $pc = $employee->payrollComponent;
                $total = ($pc?->base_salary ?? 0)
                    + (($pc?->meal_rate ?? 0) * $employee->hari_hadir)
                    + (($pc?->transport_rate ?? 0) * $employee->hari_hadir)
                    + ($pc?->allowance ?? 0)
                    - $employee->potongan_telat;
            @endphp
            <tr class="payrow">
                <form action="{{ route('payroll.update', $employee) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <td>
                        <div style="font-weight:700; font-size:12.5px;">{{ $employee->full_name }}</div>
                        <div class="row-updated">{{ $employee->employee_code }}</div>
                    </td>
                    <td><input type="number" name="base_salary" class="cell-input" value="{{ $pc->base_salary ?? 0 }}"></td>
                    <td><input type="number" name="meal_rate" class="cell-input" style="width:74px;" value="{{ $pc->meal_rate ?? 0 }}"></td>
                    <td><input type="number" name="transport_rate" class="cell-input" style="width:74px;" value="{{ $pc->transport_rate ?? 0 }}"></td>
                    <td><input type="text" class="cell-input" style="width:52px;" value="{{ $employee->hari_hadir }}" disabled></td>
                    <td><input type="number" name="allowance" class="cell-input" value="{{ $pc->allowance ?? 0 }}"></td>
                    <td><input type="text" class="cell-input" value="Rp {{ number_format($employee->potongan_telat, 0, ',', '.') }}" disabled></td>
                    <td>
                        <label class="thr-check">
                            <input type="checkbox" name="thr_active" value="1" {{ ($pc->thr_active ?? false) ? 'checked' : '' }}>
                            {{ ($pc->thr_active ?? false) ? 'Aktif' : 'Belum' }}
                        </label>
                    </td>
                    <td class="row-total mono">Rp {{ number_format($total, 0, ',', '.') }}</td>
                    <td>
                        {{-- hourly_rate karyawan tetap selalu 0, dikirim tersembunyi --}}
                        <input type="hidden" name="hourly_rate" value="0">
                        <button type="submit" class="btn btn-line btn-sm">Simpan</button>
                    </td>
                </form>
            </tr>
        @empty
            <tr><td colspan="10" style="text-align:center; color:#9AA0A8;">Belum ada karyawan tetap.</td></tr>
        @endforelse
    </table>
</div>

{{-- ============================================================ --}}
{{-- Karyawan Part Time - Gaji Per Bulan (fee + makan/bensin harian) --}}
{{-- ============================================================ --}}
<div class="divider-label">Karyawan Part Time — Gaji Bulanan</div>
<div class="field-hint" style="margin-bottom:10px;">
    Kehadiran tidak perlu diinput manual. Sistem mengambil jumlah hari hadir dari tabel absensi untuk bulan {{ $periodeLabel }}.
    Uang Makan + Bensin dihitung otomatis: Rp{{ number_format($rateMakanBensin, 0, ',', '.') }}/hari × jumlah hari hadir.
</div>
<div class="table-wrap">
    <table class="paytable">
        <tr>
            <th>Karyawan</th>
            <th>Fee Mengajar</th>
            <th>Kehadiran</th>
            <th>Makan + Bensin</th>
            <th>Bonus</th>
            <th>Total</th>
            <th></th>
        </tr>
 
        @forelse ($partTimeEmployees as $employee)
            @php
                $pc = $employee->payrollComponent;
                $total = ($pc?->base_salary ?? 0)
                    + $employee->makan_bensin
                    + ($pc?->allowance ?? 0);
            @endphp
            <tr class="payrow">
                <form action="{{ route('payroll.update', $employee) }}" method="POST">
                    @csrf
                    @method('PATCH')
 
                    <td>
                        <div style="font-weight:700; font-size:12.5px;">{{ $employee->full_name }}</div>
                        <div class="row-updated">{{ $employee->employee_code }} · {{ $employee->position }}</div>
                    </td>
                    <td><input type="number" name="base_salary" class="cell-input" value="{{ $pc->base_salary ?? 0 }}"></td>
                    <td><input type="text" class="cell-input" style="width:60px;" value="{{ $employee->hari_hadir }} hari" disabled></td>
                    <td><input type="text" class="cell-input" value="Rp {{ number_format($employee->makan_bensin, 0, ',', '.') }}" disabled></td>
                    <td><input type="number" name="allowance" class="cell-input" value="{{ $pc->allowance ?? 0 }}"></td>
                    <td class="row-total mono">Rp {{ number_format($total, 0, ',', '.') }}</td>
                    <td>
                        {{-- meal_rate, transport_rate, hourly_rate, thr_active tidak dipakai untuk part-time di desain ini --}}
                        <input type="hidden" name="meal_rate" value="0">
                        <input type="hidden" name="transport_rate" value="0">
                        <input type="hidden" name="hourly_rate" value="0">
                        <button type="submit" class="btn btn-line btn-sm">Simpan</button>
                    </td>
                </form>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center; color:#9AA0A8;">Belum ada karyawan part-time.</td></tr>
        @endforelse
    </table>
</div>
 
<form action="{{ route('payroll.publish') }}" method="POST" style="margin-top:16px;"
      onsubmit="return confirm('Terbitkan slip gaji periode {{ $periodeLabel }} untuk SEMUA karyawan? Data slip gaji lama periode ini akan ditimpa.');">
    @csrf
    <input type="hidden" name="periode" value="{{ $periodeValue }}">
    <button type="submit" class="btn btn-gold">Simpan &amp; Terbitkan Semua</button>
</form>
@endsection