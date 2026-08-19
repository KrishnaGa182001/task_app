<?php

namespace App\Models;

use App\Enums\SeatStatus;
use App\Enums\SeatTier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'seat_no',
        'status',
        'tier',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'status' => SeatStatus::class,
            'tier' => SeatTier::class,
            'version' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_seats')
            ->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(SeatAuditLog::class);
    }
}
