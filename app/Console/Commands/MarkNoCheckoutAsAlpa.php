<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkNoCheckoutAsAlpa extends Command
{
    protected $signature = 'attendance:mark-no-checkout';

    protected $description = 'Tandai alpa untuk karyawan yang check-in tapi tidak check-out setelah jam shift berakhir';

    public function handle(): int
    {
        $attendances = Attendance::query()
            ->with('shift')
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->where('manual_override', false)
            ->where('status', '!=', 'alpa')
            ->get();

        $count = 0;

        foreach ($attendances as $attendance) {
            $shiftEndTime = $attendance->shift?->end_time;

            if (! $shiftEndTime) {
                continue; // presensi tanpa shift (mis. part time), skip
            }

            $tanggal = Carbon::parse($attendance->tanggal)->format('Y-m-d');
            $shiftEndDateTime = Carbon::parse($tanggal.' '.$shiftEndTime);

            if (now()->greaterThan($shiftEndDateTime)) {
                $attendance->update(['status' => 'alpa']);
                $count++;
            }
        }

        $this->info("Selesai. {$count} data presensi ditandai alpa (tidak checkout).");

        return self::SUCCESS;
    }
}