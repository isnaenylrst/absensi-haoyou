<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThrRecord extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'thr_records';

    protected $fillable = [
        'employee_id',
        'year',
        'eligible',
        'amount',
        'note',
    ];

    protected $casts = [
        'year' => 'integer',
        'eligible' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
