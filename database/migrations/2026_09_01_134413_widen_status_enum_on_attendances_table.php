<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE attendances
            MODIFY COLUMN status ENUM('tepat_waktu', 'terlambat', 'tidak_checkout', 'cuti', 'alpa') NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE attendances
            MODIFY COLUMN status ENUM('tepat_waktu', 'terlambat', 'alpa') NULL
        ");
    }
};