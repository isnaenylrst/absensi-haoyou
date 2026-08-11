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
        'hari_hadir',
        'total_pendapatan',
        'total_potongan',
        'total_diterima',
        'published_at',
    ];

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'hari_hadir' => 'integer',
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
