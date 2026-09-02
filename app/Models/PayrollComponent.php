<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollComponent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'base_salary',
        'meal_rate',
        'transport_rate',
        'hourly_rate',
        'allowance',
        'bonus_kerajinan',
        'bonus_kinerja',
        'thr_manual',
        'thr_active',
        'effective_date',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'meal_rate' => 'decimal:2',
        'transport_rate' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'allowance' => 'decimal:2',
        'bonus_kerajinan' => 'decimal:2',
        'bonus_kinerja' => 'decimal:2',
        'thr_manual' => 'decimal:2',
        'thr_active' => 'boolean',
        'effective_date' => 'date',
        'updated_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}