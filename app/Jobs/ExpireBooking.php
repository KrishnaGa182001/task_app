<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Enums\SeatStatus;
use App\Models\Booking;
use App\Models\Seat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ExpireBooking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $bookingId)
    {
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $booking = Booking::where('id', $this->bookingId)
                ->lockForUpdate()
                ->first();

            if (!$booking || $booking->status !== BookingStatus::PENDING) {
                return;
            }

            if ($booking->expires_at && $booking->expires_at->isFuture()) {
                return;
            }

            $seatIds = $booking->seats()->pluck('seats.id')->sort()->values()->toArray();
            if (!empty($seatIds)) {
                Seat::whereIn('id', $seatIds)->lockForUpdate()->get();

                Seat::whereIn('id', $seatIds)->update([
                    'status' => SeatStatus::AVAILABLE->value,
                ]);
            }

            $booking->update([
                'status' => BookingStatus::EXPIRED,
            ]);
        });
    }
}
