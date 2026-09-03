<?php

namespace App\Exports;

use App\Models\Attendance;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceExport implements FromCollection, WithHeadings
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection(): Collection
    {
        $query = Attendance::with('employee');

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('tanggal', [$this->startDate, $this->endDate]);
        } elseif ($this->endDate) {
            $query->where('tanggal', '<', $this->endDate);
        }

        return $query->get()->map(function ($item) {
            return [
                'Nama Karyawan' => $item->employee->full_name ?? '-',
                'Tanggal'       => $item->tanggal->format('Y-m-d'),
                'Jam Masuk'     => optional($item->check_in)->format('H:i'),
                'Jam Keluar'    => optional($item->check_out)->format('H:i'),
                'Status'        => $item->status,
                'Cabang'        => $item->branch->name ?? '-',
                'Telat (menit)' => $item->late_minutes,
            ];
        });
    }

    public function headings(): array
    {
        return ['Nama Karyawan', 'Tanggal', 'Jam Masuk', 'Jam Keluar', 'Status', 'Cabang', 'Telat (menit)'];
    }
}