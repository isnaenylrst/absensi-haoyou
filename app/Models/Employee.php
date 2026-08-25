<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'employee_code',
        'full_name',
        'gender',
        'nationality',
        'religion',
        'blood_type',
        'birth_place',
        'birth_date',
        'marital_status',
        'last_education',
        'phone',
        'email',
        'address',
        'position',
        'employee_type',
        'can_submit_teaching_sessions',
        'join_date',
        'nik',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'join_date' => 'date',
        'can_submit_teaching_sessions' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function shiftSchedules(): HasMany
    {
        return $this->hasMany(ShiftSchedule::class);
    }

    public function partTimeSchedules(): HasMany
    {
        return $this->hasMany(PartTimeSchedule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function clientVisits(): HasMany
    {
        return $this->hasMany(ClientVisit::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function payrollComponent(): HasOne
    {
        return $this->hasOne(PayrollComponent::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function thrRecords(): HasMany
    {
        return $this->hasMany(ThrRecord::class);
    }

    public function initials(): string
    {
        $parts = explode(' ', trim($this->full_name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $second = mb_substr($parts[1] ?? '', 0, 1);
        return mb_strtoupper($first . $second);
    }
}