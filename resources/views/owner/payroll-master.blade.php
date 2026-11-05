@extends('owner.dashboard')

@section('title', 'Pengaturan Gaji')

@section('content')
<div class="crumb">Home <span>›</span> <a href="{{ route('payroll.index') }}">Payroll</a> <span>›</span> <b>Pengaturan Gaji</b></div>
<div class="page-title" style="margin-bottom:18px;">Pengaturan Gaji Karyawan Tetap</div>

@if (session('success'))
    <div class="badge badge-green" style="display:block; padding:10px 14px; margin-bottom:16px;">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="owner-panel-note" style="background:#FCEAE7; border-color:#F5CFC8; margin-bottom:16px;">
        {{ $errors->first() }}
    </div>
@endif

<div class="owner-panel-note" style="margin-bottom:18px;">
    <div>
        <strong>Cara kerja:</strong> gaji pokok, bonus kerajinan, dan bonus kinerja tiap karyawan diisi di halaman ini lalu <strong>dikunci</strong>.
        Setiap bulan nominalnya otomatis dipakai di halaman Payroll.
        Untuk mengubahnya, klik <strong>Edit</strong>, ubah nominal, lalu <strong>Simpan &amp; Kunci</strong>.
        <br>
        Slip gaji yang sudah diterbitkan tidak berubah meskipun data diedit.
    </div>
</div>

{{-- ============================================================
TARIF PER HARI (info saja, diatur di menu Pengaturan)
============================================================ --}}
<div class="divider-label" style="margin-top:0;">Tarif per Hari Hadir</div>

<div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:22px;">
    <div style="background:#fff; border:1px solid #EDEEF0; border-radius:10px; padding:12px 16px; min-width:200px;">
        <div style="font-size:11px; color:#888;">Uang makan / hari hadir</div>
        <div style="font-weight:800;">Rp {{ number_format($rateMakan, 0, ',', '.') }}</div>
        <div style="font-size:10px; color:#888; margin-top:3px;">Diatur di menu Pengaturan</div>
    </div>

    <div style="background:#fff; border:1px solid #EDEEF0; border-radius:10px; padding:12px 16px; min-width:200px;">
        <div style="font-size:11px; color:#888;">Uang bensin / hari hadir</div>
        <div style="font-weight:800;">Rp {{ number_format($rateBensin, 0, ',', '.') }}</div>
        <div style="font-size:10px; color:#888; margin-top:3px;">Diatur di menu Pengaturan</div>
    </div>
</div>

{{-- ============================================================
GAJI & BONUS PER KARYAWAN
============================================================ --}}
<div class="divider-label">Gaji dan Bonus per Karyawan</div>

<div class="table-wrap">
    <table class="paytable">
        <tr>
            <th>Karyawan</th>
            <th>Gaji Pokok / Bulan</th>
            <th>Bonus Kerajinan / Bulan</th>
            <th>Bonus Kinerja / Bulan</th>
            <th>Status</th>
            <th></th>
        </tr>

        @forelse ($employees as $employee)
            @php
                $pc      = $employee->payrollComponent;
                $locked  = (bool) ($pc?->gaji_locked ?? false);
                $gaji    = (int) ($pc?->base_salary ?? 0);
                $kerajinan = (int) ($pc?->bonus_kerajinan ?? 0);
                $kinerja = (int) ($pc?->bonus_kinerja ?? 0);
                $formId  = 'gaji-form-' . $employee->id;
            @endphp

            <tr class="payrow">
                <td>
                    <div style="font-weight:700; font-size:12.5px;">{{ $employee->full_name }}</div>
                    <div class="row-updated">
                        {{ $employee->employee_code }}
                        @if ($employee->position) · {{ $employee->position }} @endif
                    </div>
                </td>

                <td>
                    <input type="number" name="base_salary" form="{{ $formId }}"
                           class="cell-input" min="0" step="1000" value="{{ $gaji }}"
                           style="width:150px;" {{ $locked ? 'disabled' : '' }}>
                </td>

                <td>
                    <input type="number" name="bonus_kerajinan" form="{{ $formId }}"
                           class="cell-input" min="0" step="1000" value="{{ $kerajinan }}"
                           style="width:130px;" {{ $locked ? 'disabled' : '' }}>
                </td>

                <td>
                    <input type="number" name="bonus_kinerja" form="{{ $formId }}"
                           class="cell-input" min="0" step="1000" value="{{ $kinerja }}"
                           style="width:130px;" {{ $locked ? 'disabled' : '' }}>
                </td>

                <td>
                    @if ($locked)
                        <span class="badge badge-green">🔒 Terkunci</span>
                    @elseif ($gaji > 0)
                        <span class="badge" style="background:#FFF6E5; color:#B7791F;">Sedang diedit</span>
                    @else
                        <span class="badge" style="background:#FCEAE7; color:#D34D3C;">Belum diatur</span>
                    @endif
                </td>

                <td>
                    <form id="{{ $formId }}"
                          action="{{ route('payroll.master.update', $employee) }}"
                          method="POST">
                        @csrf
                        @method('PATCH')

                        @if ($locked)
                            <input type="hidden" name="action" value="unlock">
                            <button type="submit" class="btn btn-line btn-sm">Edit</button>
                        @else
                            <input type="hidden" name="action" value="save">
                            <button type="submit" class="btn btn-gold btn-sm"
                                    onclick="return confirm('Simpan dan kunci data gaji {{ $employee->full_name }}?')">
                                Simpan &amp; Kunci
                            </button>
                        @endif
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center; color:#9AA0A8; padding:25px;">
                    Belum ada karyawan tetap.
                </td>
            </tr>
        @endforelse
    </table>
</div>

<div style="margin-top:16px;">
    <a href="{{ route('payroll.index') }}" class="btn btn-line btn-sm">← Kembali ke Payroll</a>
</div>
@endsection