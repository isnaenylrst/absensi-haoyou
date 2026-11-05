<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Payroll {{ $periodeLabel }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        .sub { font-size: 10px; color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 5px; }
        th { background: #f1f1f1; font-size: 8px; text-align: center; }
        td.r { text-align: right; white-space: nowrap; }
        td.c { text-align: center; }
        tr.total td { font-weight: bold; background: #fafafa; }
        .ttd { margin-top: 28px; width: 100%; }
        .ttd td { border: none; text-align: center; width: 50%; }
        .foot { margin-top: 14px; font-size: 8px; color: #777; }
    </style>
</head>
<body>

    <h1>Rekap Payroll — Haoyou Educator</h1>
    <div class="sub">Periode: <strong>{{ $periodeLabel }}</strong></div>

    <table>
        <thead>
            <tr>
                <th style="width:3%;">No</th>
                <th>Karyawan</th>
                <th>Hari Hadir</th>
                <th>Gaji Pokok</th>
                <th>Uang Makan</th>
                <th>Uang Bensin</th>
                <th>Bonus Kerajinan</th>
                <th>Bonus Kinerja</th>
                <th>Potongan Telat</th>
                <th>THR</th>
                <th>Total Diterima</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payslips as $i => $slip)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>
                        {{ $slip->employee->full_name }}<br>
                        <span style="color:#777;">{{ $slip->employee->employee_code }}</span>
                    </td>
                    <td class="c">{{ $slip->hari_hadir }}</td>
                    <td class="r">Rp {{ number_format($slip->gaji_pokok, 0, ',', '.') }}</td>
                    <td class="r">Rp {{ number_format($slip->uang_makan, 0, ',', '.') }}</td>
                    <td class="r">Rp {{ number_format($slip->uang_bensin, 0, ',', '.') }}</td>
                    <td class="r">Rp {{ number_format($slip->bonus_kerajinan, 0, ',', '.') }}</td>
                    <td class="r">Rp {{ number_format($slip->bonus_kinerja, 0, ',', '.') }}</td>
                    <td class="r">Rp {{ number_format($slip->potongan_telat, 0, ',', '.') }}</td>
                    <td class="r">Rp {{ number_format($slip->thr, 0, ',', '.') }}</td>
                    <td class="r"><strong>Rp {{ number_format($slip->total_diterima, 0, ',', '.') }}</strong></td>
                </tr>
            @endforeach

            <tr class="total">
                <td colspan="10" style="text-align:right;">Total Diterima Keseluruhan</td>
                <td class="r">Rp {{ number_format($payslips->sum('total_diterima'), 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="ttd">
        <tr>
            <td>Dibuat oleh,<br><br><br><br><u>{{ $dicetakOleh }}</u><br>Owner</td>
            <td>Mengetahui,<br><br><br><br><u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u><br>Pusat</td>
        </tr>
    </table>

    <div class="foot">Dicetak pada {{ $dicetakPada }}</div>

</body>
</html>