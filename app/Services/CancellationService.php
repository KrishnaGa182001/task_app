<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\SeatStatus;
use App\Exceptions\InvalidBookingStateException;
use App\Models\Booking;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CancellationService
{
    public function cancelBooking(User $user, int $bookingId): Booking
    {
        return DB::transaction(function () use ($user, $bookingId) {
            $booking = Booking::where('id', $bookingId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->user_id !== $user->id) {
                throw new AuthorizationException('Unauthorized access to booking.');
            }

            if ($booking->status !== BookingStatus::PENDING) {
                throw new InvalidBookingStateException('The booking is no longer pending.');
            }

            $seatIds = $booking->seats()->pluck('seats.id')->sort()->values()->toArray();
            if (!empty($seatIds)) {
                Seat::whereIn('id', $seatIds)->lockForUpdate()->get();

                Seat::whereIn('id', $seatIds)->update([
                    'status' => SeatStatus::AVAILABLE->value,
                ]);
            }

            $booking->update([
                'status' => BookingStatus::CANCELLED,
            ]);

            return $booking->load(['event', 'seats']);
        });
    }
}
