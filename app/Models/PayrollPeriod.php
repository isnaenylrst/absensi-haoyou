<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    protected $fillable = [
        'period_month',
        'period_year',
        'hari_efektif',
    ];

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'hari_efektif' => 'integer',
    ];
}