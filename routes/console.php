<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Export lalu soft delete data attendance tiap bulan
Schedule::command('attendance:prune')->monthly();

// Hapus file arsip .xlsx yang sudah lebih dari 1 tahun
Schedule::command('attendance:prune-archives')->monthly();
