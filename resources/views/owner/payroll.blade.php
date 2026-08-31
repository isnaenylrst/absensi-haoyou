@extends('owner.dashboard')

@section('title', 'Payroll')

@section('content')

<div class="crumb">
    Home <span>›</span> <b>Payroll</b>
</div>

<div class="page-title" style="margin-bottom:18px;">
    Payroll
</div>

{{-- ============================================================
PERIODE
============================================================ --}}

<form method="GET"
      action="{{ route('payroll.index') }}"
      id="periode-form"
      style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">

<label style="font-size:12px; font-weight:700; color:#666;">
    Periode:
</label>

@php
    $bulanList = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    [$selectedYear, $selectedMonth] = explode('-', $periodeValue);

    $selectedMonth = (int) $selectedMonth;
    $selectedYear = (int) $selectedYear;

    $tahunMulai = now()->year - 3;
    $tahunSelesai = now()->year + 1;
@endphp

{{-- BULAN --}}
<select id="periode-bulan"
        onchange="
            document.getElementById('periode-hidden').value =
            document.getElementById('periode-tahun').value + '-' +
            document.getElementById('periode-bulan').value;

            document.getElementById('periode-form').submit();
        "
        style="
            padding:6px 10px;
            border:1px solid #ddd;
            border-radius:6px;
            font-size:12px;
            font-weight:700;
            color:#666;
            font-family:inherit;
        ">

    @foreach ($bulanList as $num => $label)

        <option value="{{ str_pad($num, 2, '0', STR_PAD_LEFT) }}"
            {{ $num === $selectedMonth ? 'selected' : '' }}>

            {{ $label }}

        </option>

    @endforeach

</select>

{{-- TAHUN --}}
<select id="periode-tahun"
        onchange="
            document.getElementById('periode-hidden').value =
            document.getElementById('periode-tahun').value + '-' +
            document.getElementById('periode-bulan').value;

            document.getElementById('periode-form').submit();
        "
        style="
            padding:6px 10px;
            border:1px solid #ddd;
            border-radius:6px;
            font-size:12px;
            font-weight:700;
            color:#666;
            font-family:inherit;
        ">

    @for ($y = $tahunSelesai; $y >= $tahunMulai; $y--)

        <option value="{{ $y }}"
            {{ $y === $selectedYear ? 'selected' : '' }}>

            {{ $y }}

        </option>

    @endfor

</select>

<input type="hidden"
       id="periode-hidden"
       name="periode"
       value="{{ $periodeValue }}">

{{-- RIWAYAT --}}
<a href="{{ route('payroll.history') }}"
   class="btn btn-line btn-sm"
   style="margin-left:auto;">

    Riwayat Payroll
</a>


</form>

{{-- ============================================================
INFORMASI PERIODE
============================================================ --}}

@unless ($isCurrentPeriode)
<div class="owner-panel-note"
     style="
        background:#FFF6E5;
        border-color:#E9C46A;
     ">

    Anda sedang melihat periode
    <strong>{{ $periodeLabel }}</strong>
    (bukan bulan berjalan).

    Perubahan komponen gaji akan tersimpan sebagai
    <strong>rate terkini</strong>
    dan berlaku untuk perhitungan ke depan.

    Untuk melihat slip yang sudah pernah diterbitkan,
    buka

    <a href="{{ route('payroll.history') }}">
        Riwayat Payroll
    </a>.

</div>

@endunless

{{-- ============================================================
SUCCESS MESSAGE
============================================================ --}}

@if (session('success'))
<div class="badge badge-green"
     style="
        display:block;
        padding:10px 14px;
        margin-bottom:16px;
     ">

    {{ session('success') }}

</div>
@endif

{{-- ============================================================
INFORMASI PERHITUNGAN
============================================================ --}}

<div class="owner-panel-note">
<svg width="15"
     height="15"
     viewBox="0 0 24 24"
     fill="none"
     stroke="#215A3D"
     stroke-width="2"
     style="
        flex-shrink:0;
        margin-top:1px;
     ">

    <circle cx="12" cy="12" r="9"/>

    <path d="M12 8v5M12 16h.01"/>

</svg>

<div>

    <strong>Aturan Payroll:</strong>

    <br>

    <strong>Karyawan Tetap:</strong>

    Gaji Pokok
    +
    Uang Makan
    +
    Uang Bensin
    +
    Bonus
    −
    Potongan Telat.

    <br>

    <strong>Part Time:</strong>

    Fee Mengajar
    +
    Uang Makan
    +
    Uang Bensin
    +
    Bonus.

    <br>

    Uang makan =
    <strong>Rp10.000 / hari hadir</strong>.

    Uang bensin =
    <strong>Rp10.000 / hari hadir</strong>.

    <br>

    <strong>Part-time tidak mendapatkan potongan keterlambatan.</strong>

    Jumlah hari hadir dan potongan keterlambatan dihitung otomatis
    dari data presensi.

</div>
</div>

{{-- ============================================================
KARYAWAN TETAP
============================================================ --}}

<div class="divider-label"
     style="margin-top:0;">

Karyawan Tetap — Gaji Bulanan

</div>

<div class="table-wrap">
<table class="paytable">

    <tr>

        <th>Karyawan</th>

        <th>
            Gaji Pokok
            <br>
            <span style="
                font-weight:600;
                text-transform:none;
                font-size:10px;
            ">
                /bulan
            </span>
        </th>

        <th>
            Uang Makan
            <br>
            <span style="
                font-weight:600;
                text-transform:none;
                font-size:10px;
            ">
                Rp10.000 /hari
            </span>
        </th>

        <th>
            Uang Bensin
            <br>
            <span style="
                font-weight:600;
                text-transform:none;
                font-size:10px;
            ">
                Rp10.000 /hari
            </span>
        </th>

        <th>
            Hari Hadir
        </th>

        <th>
            Bonus
        </th>

        <th>
            Potongan Telat
        </th>

        <th>
            Total Diterima
        </th>

        <th></th>

    </tr>
    @forelse ($tetapEmployees as $employee)

        @php

            $pc = $employee->payrollComponent;

            /*
            |--------------------------------------------------------------------------
            | UANG MAKAN
            |--------------------------------------------------------------------------
            */

            $uangMakan =
                10000 * $employee->hari_hadir;


            /*
            |--------------------------------------------------------------------------
            | UANG BENSIN
            |--------------------------------------------------------------------------
            */

            $uangBensin =
                10000 * $employee->hari_hadir;


            /*
            |--------------------------------------------------------------------------
            | GAJI POKOK
            |--------------------------------------------------------------------------
            */

            $gajiPokok =
                (float) ($pc?->base_salary ?? 0);


            /*
            |--------------------------------------------------------------------------
            | BONUS
            |--------------------------------------------------------------------------
            */

            $bonus =
                (float) ($pc?->allowance ?? 0);

            /*
            |--------------------------------------------------------------------------
            | POTONGAN TELAT
            |--------------------------------------------------------------------------
            */

            $potonganTelat =
                (float) ($employee->potongan_telat ?? 0);


            /*
            |--------------------------------------------------------------------------
            | TOTAL
            |--------------------------------------------------------------------------
            */

            $total =
                $gajiPokok
                + $uangMakan
                + $uangBensin
                + $bonus
                - $potonganTelat;

        @endphp


        <tr class="payrow">

            <form action="{{ route('payroll.update', $employee) }}"
                  method="POST">

                @csrf

                @method('PATCH')


                {{-- KARYAWAN --}}
                <td>

                    <div style="
                        font-weight:700;
                        font-size:12.5px;
                    ">

                        {{ $employee->full_name }}

                    </div>

                    <div class="row-updated">

                        {{ $employee->employee_code }}

                    </div>

                </td>


                {{-- GAJI POKOK --}}
                <td>

                    <input type="number"
                           name="base_salary"
                           class="cell-input"
                           min="0"
                           value="{{ $pc?->base_salary ?? 0 }}">

                </td>


                {{-- UANG MAKAN --}}
                <td>

                    <div style="
                        font-size:12px;
                        font-weight:700;
                        white-space:nowrap;
                    ">

                        Rp {{ number_format($uangMakan, 0, ',', '.') }}

                    </div>

                    <div style="
                        font-size:10px;
                        color:#888;
                        margin-top:3px;
                    ">

                        Rp10.000 × {{ $employee->hari_hadir }} hari

                    </div>

                </td>


                {{-- UANG BENSIN --}}
                <td>

                    <div style="
                        font-size:12px;
                        font-weight:700;
                        white-space:nowrap;
                    ">

                        Rp {{ number_format($uangBensin, 0, ',', '.') }}

                    </div>

                    <div style="
                        font-size:10px;
                        color:#888;
                        margin-top:3px;
                    ">

                        Rp10.000 × {{ $employee->hari_hadir }} hari

                    </div>

                </td>


                {{-- HARI HADIR --}}
                <td>

                    <input type="text"
                           class="cell-input"
                           style="width:60px;"
                           value="{{ $employee->hari_hadir }} hari"
                           disabled>

                </td>


                {{-- BONUS --}}
                <td>

                    <input type="number"
                           name="allowance"
                           class="cell-input"
                           min="0"
                           value="{{ $pc?->allowance ?? 0 }}">

                </td>


                {{-- POTONGAN --}}
                <td>

                    <div style="
                        color:{{ $potonganTelat > 0 ? '#B45309' : '#666' }};
                        font-weight:700;
                        white-space:nowrap;
                    ">

                        Rp {{ number_format($potonganTelat, 0, ',', '.') }}

                    </div>

                    @if ($potonganTelat > 0)

                        <div style="
                            font-size:10px;
                            color:#888;
                            margin-top:3px;
                        ">

                            {{ $employee->total_telat_menit ?? 0 }}
                            menit telat

                        </div>

                    @else

                        <div style="
                            font-size:10px;
                            color:#888;
                            margin-top:3px;
                        ">

                            Tidak ada potongan

                        </div>

                    @endif

                </td>


                {{-- TOTAL --}}
                <td>

                    <div class="row-total mono"
                         style="
                            white-space:nowrap;
                            font-weight:800;
                         ">

                        Rp {{ number_format($total, 0, ',', '.') }}

                    </div>

                </td>


                {{-- ACTION --}}
                <td>

                    <input type="hidden"
                           name="meal_rate"
                           value="10000">

                    <input type="hidden"
                           name="transport_rate"
                           value="10000">

                    <input type="hidden"
                           name="hourly_rate"
                           value="0">


                    <label class="thr-check"
                           style="
                                display:block;
                                margin-bottom:7px;
                           ">

                        <input type="checkbox"
                               name="thr_active"
                               value="1"
                               {{ ($pc?->thr_active ?? false) ? 'checked' : '' }}>

                        {{ ($pc?->thr_active ?? false)
                            ? 'THR Aktif'
                            : 'THR Belum'
                        }}

                    </label>


                    <button type="submit"
                            class="btn btn-line btn-sm">

                        Simpan

                    </button>

                </td>

            </form>

        </tr>


    @empty

        <tr>

            <td colspan="9"
                style="
                    text-align:center;
                    color:#9AA0A8;
                    padding:25px;
                ">

                Belum ada karyawan tetap.

            </td>

        </tr>

    @endforelse

</table>
</div>

{{-- ============================================================
KARYAWAN PART TIME
============================================================ --}}

<div class="divider-label">
    Karyawan Part Time — Fee Mengajar
</div>

<div class="field-hint" style="margin-bottom:10px;">

```
Fee mengajar, uang makan, dan uang bensin
diinput manual oleh Owner.

Tidak menggunakan absensi dan tidak menggunakan
perhitungan Rp10.000 per hari.
```

</div>

<div class="table-wrap">

```
<table class="paytable">

    <tr>

        <th>Karyawan</th>

        <th>
            Fee Mengajar
        </th>

        <th>
            Uang Makan
        </th>

        <th>
            Uang Bensin
        </th>

        <th>
            Total Diterima
        </th>

        <th></th>

    </tr>


    @forelse ($partTimeEmployees as $employee)

        @php

            $pc = $employee->payrollComponent;

            /*
            |--------------------------------------------------------------------------
            | FEE MENGAJAR
            |--------------------------------------------------------------------------
            */
            $feeMengajar =
                (float) ($pc?->base_salary ?? 0);


            /*
            |--------------------------------------------------------------------------
            | UANG MAKAN
            |--------------------------------------------------------------------------
            | Diambil dari meal_rate karena controller
            | menyimpan input manual Owner ke field ini.
            */
            $uangMakan =
                (float) ($pc?->meal_rate ?? 0);


            /*
            |--------------------------------------------------------------------------
            | UANG BENSIN
            |--------------------------------------------------------------------------
            | Diambil dari transport_rate karena controller
            | menyimpan input manual Owner ke field ini.
            */
            $uangBensin =
                (float) ($pc?->transport_rate ?? 0);


            /*
            |--------------------------------------------------------------------------
            | TOTAL DITERIMA
            |--------------------------------------------------------------------------
            */
            $total =
                $feeMengajar
                + $uangMakan
                + $uangBensin;

        @endphp


        <tr class="payrow">

            <form
                action="{{ route('payroll.update', $employee) }}"
                method="POST"
            >

                @csrf

                @method('PATCH')


                {{-- ====================================================
                KARYAWAN
                ==================================================== --}}

                <td>

                    <div style="
                        font-weight:700;
                        font-size:12.5px;
                    ">

                        {{ $employee->full_name }}

                    </div>


                    <div class="row-updated">

                        {{ $employee->employee_code }}

                        @if ($employee->position)

                            · {{ $employee->position }}

                        @endif

                    </div>

                </td>


                {{-- ====================================================
                FEE MENGAJAR
                ==================================================== --}}

                <td>

                    <input
                        type="number"
                        name="base_salary"
                        class="cell-input"
                        min="0"
                        step="1"
                        value="{{ $feeMengajar }}"
                        oninput="hitungTotal{{ $employee->id }}()"
                    >

                    <div style="
                        font-size:10px;
                        color:#888;
                        margin-top:3px;
                    ">

                        Input Owner

                    </div>

                </td>


                {{-- ====================================================
                UANG MAKAN
                ==================================================== --}}

                <td>

                    <input
                        type="number"
                        id="meal-{{ $employee->id }}"
                        name="meal_rate"
                        class="cell-input"
                        min="0"
                        step="1"
                        value="{{ $uangMakan }}"
                        oninput="hitungTotal{{ $employee->id }}()"
                        placeholder="0"
                    >

                    <div style="
                        font-size:10px;
                        color:#888;
                        margin-top:3px;
                    ">

                        Input Owner

                    </div>

                </td>


                {{-- ====================================================
                UANG BENSIN
                ==================================================== --}}

                <td>

                    <input
                        type="number"
                        id="transport-{{ $employee->id }}"
                        name="transport_rate"
                        class="cell-input"
                        min="0"
                        step="1"
                        value="{{ $uangBensin }}"
                        oninput="hitungTotal{{ $employee->id }}()"
                        placeholder="0"
                    >

                    <div style="
                        font-size:10px;
                        color:#888;
                        margin-top:3px;
                    ">

                        Input Owner

                    </div>

                </td>


                {{-- ====================================================
                TOTAL DITERIMA
                ==================================================== --}}

                <td>

                    <div
                        id="total-{{ $employee->id }}"
                        class="row-total mono"
                        style="
                            white-space:nowrap;
                            font-weight:800;
                        "
                    >

                        Rp {{ number_format($total, 0, ',', '.') }}

                    </div>

                </td>


                {{-- ====================================================
                SIMPAN
                ==================================================== --}}

                <td>

                    <button
                        type="submit"
                        class="btn btn-line btn-sm"
                    >

                        Simpan

                    </button>

                </td>

            </form>

        </tr>


        {{-- ============================================================
        JAVASCRIPT HITUNG TOTAL
        ============================================================ --}}

        <script>

            function hitungTotal{{ $employee->id }}() {

                /*
                |--------------------------------------------------------------------------
                | AMBIL FEE MENGAJAR
                |--------------------------------------------------------------------------
                */
                const feeInput =
                    document.querySelector(
                        '#total-{{ $employee->id }}'
                    ).closest('tr')
                    .querySelector(
                        '[name="base_salary"]'
                    );

                const fee =
                    Number(
                        feeInput?.value || 0
                    );


                /*
                |--------------------------------------------------------------------------
                | AMBIL UANG MAKAN
                |--------------------------------------------------------------------------
                */
                const makan =
                    Number(
                        document.getElementById(
                            'meal-{{ $employee->id }}'
                        )?.value || 0
                    );


                /*
                |--------------------------------------------------------------------------
                | AMBIL UANG BENSIN
                |--------------------------------------------------------------------------
                */
                const bensin =
                    Number(
                        document.getElementById(
                            'transport-{{ $employee->id }}'
                        )?.value || 0
                    );


                /*
                |--------------------------------------------------------------------------
                | TOTAL
                |--------------------------------------------------------------------------
                |
                | Fee Mengajar
                | + Uang Makan
                | + Uang Bensin
                |
                */
                const total =
                    fee
                    + makan
                    + bensin;


                /*
                |--------------------------------------------------------------------------
                | TAMPILKAN TOTAL
                |--------------------------------------------------------------------------
                */
                document.getElementById(
                    'total-{{ $employee->id }}'
                ).innerText =
                    'Rp ' +
                    total.toLocaleString('id-ID');

            }

        </script>


    @empty

        <tr>

            <td
                colspan="6"
                style="
                    text-align:center;
                    color:#9AA0A8;
                    padding:25px;
                "
            >

                Belum ada karyawan part-time.

            </td>

        </tr>

    @endforelse

</table>
</div>


{{-- ============================================================
PUBLISH
============================================================ --}}

<form action="{{ route('payroll.publish') }}"
      method="POST"
      style="margin-top:16px;"
      onsubmit="
        return confirm(
            'Terbitkan slip gaji periode {{ $periodeLabel }} untuk SEMUA karyawan? Data slip gaji lama periode ini akan ditimpa.'
        );
      ">
@csrf

<input type="hidden"
       name="periode"
       value="{{ $periodeValue }}">

<button type="submit"
        class="btn btn-gold">

    Simpan &amp; Terbitkan Semua

</button>
</form>

@endsection
