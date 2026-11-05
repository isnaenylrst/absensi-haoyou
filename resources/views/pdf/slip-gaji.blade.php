<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <style>
        @page { margin: 30px 36px; }

        body {
            font-family: 'Helvetica', sans-serif;
            color: #22262B;
            font-size: 12px;
        }

        .header {
            border-bottom: 3px solid #F5A623;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }

        .company { font-size: 18px; font-weight: bold; color: #22262B; }
        .company-sub { font-size: 10px; color: #6B7280; margin-top: 2px; }
        .doc-title { font-size: 14px; font-weight: bold; color: #DE8C0F; margin-top: 10px; }

        .info-table { width: 100%; margin-bottom: 18px; }
        .info-table td { padding: 3px 0; font-size: 11px; }
        .info-label { color: #6B7280; width: 120px; }
        .info-value { font-weight: bold; }

        table.rincian { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.rincian th {
            text-align: left; font-size: 9px; text-transform: uppercase;
            color: #9AA0A8; border-bottom: 1px solid #EDEEF0; padding: 8px 0;
        }
        table.rincian td { padding: 8px 0; border-bottom: 1px solid #F4F5F2; font-size: 11.5px; }
        table.rincian .label { font-weight: bold; }
        table.rincian .sub { font-size: 9.5px; color: #9AA0A8; }
        table.rincian .val { text-align: right; font-weight: bold; }
        table.rincian .val-pos { color: #2F8A5B; }
        table.rincian .val-neg { color: #D34D3C; }
        table.rincian .val-zero { color: #9AA0A8; }

        .section-label {
            font-size: 9px; text-transform: uppercase; letter-spacing: 1px;
            color: #9AA0A8; font-weight: bold; margin: 14px 0 6px;
        }

        .total-row { border-top: 2px solid #22262B; padding-top: 10px; margin-top: 8px; }
        .total-row table { width: 100%; }
        .total-label { font-size: 14px; font-weight: bold; }
        .total-value { font-size: 18px; font-weight: bold; color: #DE8C0F; text-align: right; }

        .footer {
            margin-top: 40px; font-size: 9px; color: #9AA0A8;
            text-align: center; border-top: 1px solid #EDEEF0; padding-top: 10px;
        }
    </style>
</head>


<body>

    {{-- HEADER --}}
    <div class="header">
        <div class="company">Haoyou Educator</div>
        <div class="company-sub">
            Jl. Terusan Dieng No.9E, Pisang Candi,
            Kec. Sukun, Kota Malang, Jawa Timur 65115
        </div>
        <div class="doc-title">
            SLIP GAJI — {{ strtoupper($periodeLabel) }}
        </div>
    </div>


    {{-- INFORMASI KARYAWAN --}}
    <table class="info-table">
        <tr>
            <td class="info-label">Nama Karyawan</td>
            <td class="info-value">: {{ $employee->full_name }}</td>
        </tr>
        <tr>
            <td class="info-label">ID Karyawan</td>
            <td class="info-value">: {{ $employee->employee_code }}</td>
        </tr>
        <tr>
            <td class="info-label">Jabatan</td>
            <td class="info-value">: {{ $employee->position }}</td>
        </tr>
        <tr>
            <td class="info-label">Status</td>
            <td class="info-value">
                : {{ $employee->employee_type === 'tetap' ? 'Karyawan Tetap' : 'Karyawan Part Time' }}
            </td>
        </tr>
        <tr>
            <td class="info-label">Diterbitkan</td>
            <td class="info-value">
                : {{ $payslip->published_at
                    ? \Carbon\Carbon::parse($payslip->published_at)->translatedFormat('d F Y')
                    : '-' }}
            </td>
        </tr>
    </table>


    {{-- PENDAPATAN --}}
    <div class="section-label">Pendapatan</div>

    <table class="rincian">

        @if ($employee->employee_type === 'part_time')

            <tr>
                <td class="label">Fee Mengajar</td>
                <td class="val val-pos">
                    Rp {{ number_format($partTime['fee'], 0, ',', '.') }}
                </td>
            </tr>

            @if ($partTime['makan'] > 0)
                <tr>
                    <td class="label">Uang Makan</td>
                    <td class="val val-pos">
                        Rp {{ number_format($partTime['makan'], 0, ',', '.') }}
                    </td>
                </tr>
            @endif

            @if ($partTime['bensin'] > 0)
                <tr>
                    <td class="label">Uang Bensin</td>
                    <td class="val val-pos">
                        Rp {{ number_format($partTime['bensin'], 0, ',', '.') }}
                    </td>
                </tr>
            @endif

        @else

            @php
                $bonusKerajinan = (float) ($rincian['bonus_kerajinan'] ?? 0);
                $bonusKinerja   = (float) ($rincian['bonus_kinerja'] ?? 0);
                $thrSlip        = (float) ($rincian['thr'] ?? 0);
                $potonganTelat  = (float) ($rincian['potongan'] ?? 0);
            @endphp

            <tr>
                <td class="label">
                    Gaji Pokok
                    <div class="sub">Gaji bulanan tetap</div>
                </td>
                <td class="val val-pos">
                    Rp {{ number_format($rincian['gaji_pokok'], 0, ',', '.') }}
                </td>
            </tr>

            <tr>
                <td class="label">
                    Uang Makan
                    <div class="sub">
                        Rp {{ number_format($tarifMakan, 0, ',', '.') }}
                        /hari × {{ $payslip->hari_hadir }} hari hadir
                    </div>
                </td>
                <td class="val val-pos">
                    Rp {{ number_format($rincian['uang_makan'], 0, ',', '.') }}
                </td>
            </tr>

            <tr>
                <td class="label">
                    Uang Bensin
                    <div class="sub">
                        Rp {{ number_format($tarifBensin, 0, ',', '.') }}
                        /hari × {{ $payslip->hari_hadir }} hari hadir
                    </div>
                </td>
                <td class="val val-pos">
                    Rp {{ number_format($rincian['uang_bensin'], 0, ',', '.') }}
                </td>
            </tr>

            {{-- Bonus Kerajinan (selalu tampil) --}}
            <tr>
                <td class="label">
                    Bonus Kerajinan
                    <div class="sub">Bonus periode ini</div>
                </td>
                <td class="val {{ $bonusKerajinan > 0 ? 'val-pos' : 'val-zero' }}">
                    Rp {{ number_format($bonusKerajinan, 0, ',', '.') }}
                </td>
            </tr>

            {{-- Bonus Kinerja (selalu tampil) --}}
            <tr>
                <td class="label">
                    Bonus Kinerja
                    <div class="sub">Bonus periode ini</div>
                </td>
                <td class="val {{ $bonusKinerja > 0 ? 'val-pos' : 'val-zero' }}">
                    Rp {{ number_format($bonusKinerja, 0, ',', '.') }}
                </td>
            </tr>

            {{-- THR (hanya jika ada di slip) --}}
            @if ($thrSlip > 0)
                <tr>
                    <td class="label">
                        THR
                        <div class="sub">Tunjangan Hari Raya</div>
                    </td>
                    <td class="val val-pos">
                        Rp {{ number_format($thrSlip, 0, ',', '.') }}
                    </td>
                </tr>
            @endif

        @endif

    </table>


    {{-- POTONGAN (hanya karyawan tetap, selalu tampil) --}}
    @if ($employee->employee_type !== 'part_time')

        <div class="section-label">Potongan</div>

        <table class="rincian">
            <tr>
                <td class="label">
                    Potongan Telat
                    <div class="sub">Total potongan keterlambatan periode ini</div>
                </td>
                @if ($potonganTelat > 0)
                    <td class="val val-neg">
                        − Rp {{ number_format($potonganTelat, 0, ',', '.') }}
                    </td>
                @else
                    <td class="val val-zero">
                        Rp 0
                    </td>
                @endif
            </tr>
        </table>

    @endif


    {{-- TOTAL DITERIMA --}}
    <div class="total-row">
        <table>
            <tr>
                <td class="total-label">Total Diterima</td>
                <td class="total-value">
                    @if ($employee->employee_type === 'part_time')
                        Rp {{ number_format($partTime['total'], 0, ',', '.') }}
                    @else
                        Rp {{ number_format($payslip->total_diterima, 0, ',', '.') }}
                    @endif
                </td>
            </tr>
        </table>
    </div>


    {{-- CATATAN PART TIME --}}
    @if ($employee->employee_type === 'part_time')

        <div style="margin-top:18px; padding:10px; border:1px solid #EDEEF0; font-size:9.5px; color:#6B7280;">
            <strong>Keterangan:</strong>
            Fee mengajar, uang makan, dan uang bensin
            untuk karyawan part time ditentukan dan diinput
            secara manual oleh Owner. Uang makan dan uang bensin
            tidak dihitung berdasarkan jumlah hari hadir.
        </div>

    @endif


    {{-- FOOTER --}}
    <div class="footer">
        Dokumen ini digenerate otomatis oleh sistem Absenly
        dan sah tanpa tanda tangan basah.
        <br>
        Dicetak pada {{ now()->translatedFormat('d F Y, H:i') }} WIB
    </div>

</body>
</html>