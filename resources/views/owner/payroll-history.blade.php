@extends('owner.dashboard')

@section('title', 'Riwayat Payroll')

@section('content')
<div class="crumb">Home <span>›</span> <a href="{{ route('payroll.index') }}">Payroll</a> <span>›</span> <b>Riwayat</b></div>
<div class="page-title" style="margin-bottom:18px;">Riwayat Payroll</div>

@if ($periods->isEmpty())
    <div class="owner-panel-note">
        Belum ada slip gaji yang diterbitkan. Terbitkan payroll dari halaman
        <a href="{{ route('payroll.index') }}">Payroll</a> terlebih dahulu.
    </div>
@else
    <form method="GET" action="{{ route('payroll.history') }}"
          style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
        <label for="periode-riwayat" style="font-size:12px; font-weight:700; color:#666;">Periode:</label>
        <select name="periode_select" id="periode-riwayat"
                onchange="const [y,m]=this.value.split('-'); window.location='{{ route('payroll.history') }}?year='+y+'&month='+m;"
                style="padding:6px 10px; border:1px solid #ddd; border-radius:6px;">
            @foreach ($periods as $p)
                <option value="{{ $p->period_year }}-{{ $p->period_month }}"
                    {{ (int) $p->period_year === (int) $selectedYear && (int) $p->period_month === (int) $selectedMonth ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::createFromDate($p->period_year, $p->period_month, 1)->translatedFormat('F Y') }}
                </option>
            @endforeach
        </select>
        <a href="{{ route('payroll.index', ['periode' => sprintf('%04d-%02d', $selectedYear, $selectedMonth)]) }}"
           class="btn btn-line btn-sm" style="margin-left:auto;">
            Lihat / Edit di Payroll
        </a>
    </form>

    <div class="divider-label" style="margin-top:0;">Slip Gaji — {{ $periodeLabel }}</div>
    <div class="table-wrap">
        <table class="paytable">
            <tr>
                <th>Karyawan</th>
                <th>Hari Hadir</th>
                <th>Total Pendapatan</th>
                <th>Total Potongan</th>
                <th>Total Diterima</th>
                <th>Diterbitkan</th>
            </tr>

            @forelse ($payslips as $slip)
                <tr class="payrow">
                    <td>
                        <div style="font-weight:700; font-size:12.5px;">{{ $slip->employee->full_name }}</div>
                        <div class="row-updated">{{ $slip->employee->employee_code }}</div>
                    </td>
                    <td>{{ $slip->hari_hadir }}</td>
                    <td class="mono">Rp {{ number_format($slip->total_pendapatan, 0, ',', '.') }}</td>
                    <td class="mono">Rp {{ number_format($slip->total_potongan, 0, ',', '.') }}</td>
                    <td class="row-total mono">Rp {{ number_format($slip->total_diterima, 0, ',', '.') }}</td>
                    <td class="row-updated">{{ $slip->published_at?->translatedFormat('d M Y, H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; color:#9AA0A8;">Tidak ada slip untuk periode ini.</td></tr>
            @endforelse

            @if ($payslips->isNotEmpty())
                <tr>
                    <td colspan="4" style="text-align:right; font-weight:700;">Total Diterima Keseluruhan</td>
                    <td class="row-total mono" style="font-weight:800;">
                        Rp {{ number_format($payslips->sum('total_diterima'), 0, ',', '.') }}
                    </td>
                    <td></td>
                </tr>
            @endif
        </table>
    </div>
@endif
@endsection