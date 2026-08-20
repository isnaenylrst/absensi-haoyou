<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Shift extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'tolerance_minutes',
        'applicable_days',
    ];

    protected $casts = [
        'tolerance_minutes' => 'integer',
        'applicable_days' => 'array',
    ];

    private const HARI_MAP = [
        1 => 'senin', 
        2 => 'selasa', 
        3 => 'rabu', 
        4 => 'kamis', 
        5 => 'jumat', 
        6 => 'sabtu', 
        7 => 'minggu'];

    public static function keyHari(\Carbon\Carbon $tanggal): string
    {
        return self::HARI_MAP[$tanggal->dayOfWeekIso];
    }

    public static function applicableTo(string $hari)
    {
        return static::query()->whereJsonContains('applicable_days', $hari)->orderBy('start_time')->get();
    }

    public static function determineForCheckIn(\Carbon\Carbon $checkIn, string $hari): ?self
    {
        return static::applicableTo($hari)
            ->sortBy(fn (self $shift) => abs(\Carbon\Carbon::parse($shift->start_time)->diffInMinutes($checkIn, false)))
            ->first();
    }

    // Menghitung status keterlambatan
    public function determineStatus(Carbon $checkIn): array
    {
        $shiftStart = Carbon::parse(
            $checkIn->toDateString().' '.$this->start_time
        );
    
        $diffMinutes = (int) round($shiftStart->diffInSeconds($checkIn, false) / 60);
    
        if ($diffMinutes <= 0) {
            return ['status' => 'tepat_waktu', 'late_minutes' => 0];
        }

        if ($diffMinutes <= $this->tolerance_minutes) {
            return ['status' => 'tepat_waktu', 'late_minutes' => 0];
        }

        return ['status' => 'terlambat', 'late_minutes' => $diffMinutes];
    }    
    
    public function shiftSchedules(): HasMany
    {
        return $this->hasMany(ShiftSchedule::class);
    }
}
