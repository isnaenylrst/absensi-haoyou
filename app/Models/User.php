<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'username',
        'password_hash',
        'role',
        'status_akun',
        'last_login',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'last_login' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Laravel auth mengharapkan kolom "password" secara default —
     * arahkan ke password_hash agar tetap kompatibel dengan Auth::attempt().
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedLeaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'approved_by');
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}
