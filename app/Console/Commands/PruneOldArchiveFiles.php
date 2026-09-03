<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PruneOldArchiveFiles extends Command
{
    protected $signature = 'attendance:prune-archives';
    protected $description = 'Hapus file arsip attendance (.xlsx) yang sudah lebih dari 1 tahun';

    public function handle()
    {
        $cutoff = Carbon::now()->subYear();
        $files = Storage::disk('local')->files('archives');

        $deletedCount = 0;

        foreach ($files as $file) {
            if (! str_ends_with($file, '.xlsx')) {
                continue;
            }

            $lastModified = Carbon::createFromTimestamp(
                Storage::disk('local')->lastModified($file)
            );

            if ($lastModified->lt($cutoff)) {
                Storage::disk('local')->delete($file);
                $deletedCount++;
                $this->info("Dihapus: {$file} (dibuat {$lastModified->format('d M Y')})");
            }
        }

        if ($deletedCount === 0) {
            $this->info('Tidak ada file arsip yang perlu dihapus.');
            return;
        }

        $this->info("Selesai. {$deletedCount} file arsip berhasil dihapus.");
    }
}