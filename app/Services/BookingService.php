<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\SeatStatus;
use App\Exceptions\SeatUnavailableException;
use App\Jobs\ExpireBooking;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function bookSeats(User $user, int $eventId, array $seatIds): Booking
    {
        $sortedSeatIds = $seatIds;
        sort($sortedSeatIds);

        $expiredBookingIds = Booking::where('status', BookingStatus::PENDING)
            ->where('expires_at', '<=', now())
            ->pluck('id');

        foreach ($expiredBookingIds as $id) {
            (new ExpireBooking($id))->handle();
        }

        return DB::transaction(function () use ($user, $eventId, $sortedSeatIds) {
            $event = Event::findOrFail($eventId);

            $seats = Seat::whereIn('id', $sortedSeatIds)
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->get();

            if ($seats->count() !== count($sortedSeatIds)) {
                throw new SeatUnavailableException('One or more requested seats are unavailable.');
            }

            foreach ($seats as $seat) {
                if ($seat->status !== SeatStatus::AVAILABLE) {
                    throw new SeatUnavailableException('One or more requested seats are unavailable.');
                }
            }

            $booking = Booking::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'status' => BookingStatus::PENDING,
                'expires_at' => now()->addMinutes(10),
            ]);

            $booking->seats()->attach($sortedSeatIds);

            Seat::whereIn('id', $sortedSeatIds)->update([
                'status' => SeatStatus::RESERVED->value,
            ]);

            return $booking->load(['event', 'seats']);
        });
    }
}
