<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'shift_schedule_id',
        'shift_id',
        'part_time_schedule_id',
        'activity',
        'branch_id',
        'tanggal',
        'check_in',
        'check_out',
        'check_in_lat',
        'check_in_lng',
        'distance_m',
        'check_in_photo_url',
        'check_out_photo_url',
        'status',
        'late_minutes',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'check_in_lat' => 'decimal:7',
        'check_in_lng' => 'decimal:7',
        'distance_m' => 'decimal:2',
        'late_minutes' => 'integer',
        'created_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function shiftSchedule(): BelongsTo
    {
        return $this->belongsTo(ShiftSchedule::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function partTimeSchedule(): BelongsTo
    {
        return $this->belongsTo(PartTimeSchedule::class);
    }
}