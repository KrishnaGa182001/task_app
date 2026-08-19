<?php

namespace App\Models;

use App\Enums\SeatTier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'seat_id',
        'old_tier',
        'new_tier',
        'admin_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_tier' => SeatTier::class,
            'new_tier' => SeatTier::class,
            'created_at' => 'datetime',
        ];
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
