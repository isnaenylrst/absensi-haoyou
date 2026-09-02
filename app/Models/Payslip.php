<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'employee_id',

        'period_month',
        'period_year',

        'hari_efektif',
        'hari_hadir',

        'gaji_pokok',
        'uang_makan',
        'uang_bensin',

        'bonus_kerajinan',
        'bonus_kinerja',

        'potongan_telat',
        'thr',

        'total_pendapatan',
        'total_potongan',
        'total_diterima',

        'published_at',
    ];

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',

        'hari_efektif' => 'integer',
        'hari_hadir' => 'integer',

        'gaji_pokok' => 'decimal:2',
        'uang_makan' => 'decimal:2',
        'uang_bensin' => 'decimal:2',

        'bonus_kerajinan' => 'decimal:2',
        'bonus_kinerja' => 'decimal:2',

        'potongan_telat' => 'decimal:2',
        'thr' => 'decimal:2',

        'total_pendapatan' => 'decimal:2',
        'total_potongan' => 'decimal:2',
        'total_diterima' => 'decimal:2',

        'published_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}