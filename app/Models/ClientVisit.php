<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ClientVisit extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'client_name',
        'address',
        'visit_type',
        'latitude',
        'longitude',
        'accuracy_m',
        'photo_url',
        'notes',
        'visited_at',
        'review_status',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'accuracy_m' => 'decimal:2',
        'visited_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Kolom photo_url di database menyimpan PATH relatif di disk 'public'
     * (misal: "seed-photos/dummy-client-visit.jpg" atau
     * "client-visit-photos/5/2026-08-19_143530_x9y8z7.jpg").
     *
     * Accessor ini otomatis mengubahnya jadi URL publik lengkap
     * (misal: "https://domainmu.com/storage/seed-photos/dummy-client-visit.jpg")
     * setiap kali diakses lewat $visit->photo_url — baik di blade, JSON,
     * maupun tempat lain. Kalau di masa depan ada data lama yang sudah
     * berupa URL penuh (http/https), accessor ini membiarkannya apa adanya.
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (!$value) {
                    return null;
                }

                if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                    return $value;
                }

                /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                $disk = Storage::disk('public');

                return $disk->url($value);
            },
        );
    }
}