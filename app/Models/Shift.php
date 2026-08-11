<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'tolerance_minutes',
    ];

    protected $casts = [
        'tolerance_minutes' => 'integer',
    ];

    public function shiftSchedules(): HasMany
    {
        return $this->hasMany(ShiftSchedule::class);
    }
}
