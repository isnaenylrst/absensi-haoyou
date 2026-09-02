<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'late_tolerance_minutes',
        'late_deduction_per_minute',
        'alpa_deduction_per_day',

        // TAMBAHKAN
        'meal_rate',
        'transport_rate',

        'thr_start_year',
        'photo_required',
        'out_of_radius_policy',
    ];

    protected $casts = [
        'late_tolerance_minutes' => 'integer',

        'late_deduction_per_minute' => 'decimal:2',
        'alpa_deduction_per_day' => 'decimal:2',

        // TAMBAHKAN
        'meal_rate' => 'decimal:2',
        'transport_rate' => 'decimal:2',

        'thr_start_year' => 'integer',
        'photo_required' => 'boolean',

        'updated_at' => 'datetime',
    ];

    /**
     * Setting bersifat singleton (1 baris untuk seluruh sistem).
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}