<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Exports\AttendanceExport;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class PruneOldAttendance extends Command
{
    protected $signature = 'attendance:prune';
    protected $description = 'Export lalu soft delete data attendance dengan tanggal lebih dari 1 bulan lalu';

    public function handle()
    {
        $cutoff = Carbon::now()->subMonth()->startOfDay(); // diubah dari subMonths(3)

        $oldData = Attendance::where('tanggal', '<', $cutoff)->get();

        if ($oldData->isEmpty()) {
            $this->info('Tidak ada data attendance yang perlu diarsipkan.');
            return;
        }

        $fileName = 'archives/attendance_archive_' . now()->format('Ymd_His') . '.xlsx';
        Excel::store(
            new AttendanceExport(null, $cutoff),
            $fileName,
            'local'
        );

        $count = Attendance::where('tanggal', '<', $cutoff)->delete();

        $this->info("Berhasil export {$oldData->count()} data ke {$fileName}, dan soft-delete {$count} data.");
    }
}