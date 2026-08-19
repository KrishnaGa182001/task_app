<?php

namespace App\Services;

use App\Enums\SeatTier;
use App\Models\Seat;
use App\Models\SeatAuditLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SeatUpgradeService
{
    public function upgradeSeat(User $admin, int $seatId, string|SeatTier $newTier): Seat
    {
        if (!$admin->is_admin) {
            throw new AuthorizationException('Only administrator accounts can upgrade seat tiers.');
        }

        $targetTier = $newTier instanceof SeatTier ? $newTier : SeatTier::from($newTier);

        return DB::transaction(function () use ($admin, $seatId, $targetTier) {
            $seat = Seat::where('id', $seatId)
                ->lockForUpdate()
                ->firstOrFail();

            $oldTier = $seat->tier;

            $seat->update([
                'tier' => $targetTier,
            ]);

            SeatAuditLog::create([
                'seat_id' => $seat->id,
                'old_tier' => $oldTier->value ?? (string) $oldTier,
                'new_tier' => $targetTier->value,
                'admin_id' => $admin->id,
                'created_at' => now(),
            ]);

            return $seat->load('auditLogs');
        });
    }
}
