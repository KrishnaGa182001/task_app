<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\SeatStatus;
use App\Exceptions\BookingExpiredException;
use App\Exceptions\InvalidBookingStateException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function processPayment(User $user, int $bookingId, string $transactionId): Payment
    {
        $existingPayment = Payment::where('transaction_id', $transactionId)->first();
        if ($existingPayment) {
            if ($existingPayment->booking->user_id !== $user->id) {
                throw new AuthorizationException('Unauthorized access to booking payment.');
            }
            return $existingPayment->load('booking');
        }

        return DB::transaction(function () use ($user, $bookingId, $transactionId) {
            $existingPayment = Payment::where('transaction_id', $transactionId)->first();
            if ($existingPayment) {
                if ($existingPayment->booking->user_id !== $user->id) {
                    throw new AuthorizationException('Unauthorized access to booking payment.');
                }
                return $existingPayment->load('booking');
            }

            $booking = Booking::where('id', $bookingId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->user_id !== $user->id) {
                throw new AuthorizationException('Unauthorized access to booking payment.');
            }

            if ($booking->status === BookingStatus::EXPIRED || ($booking->expires_at && $booking->expires_at->isPast())) {
                throw new BookingExpiredException('The booking has expired.');
            }

            if ($booking->status !== BookingStatus::PENDING) {
                throw new InvalidBookingStateException('The booking is no longer pending.');
            }

            $seatIds = $booking->seats()->pluck('seats.id')->sort()->values()->toArray();
            if (!empty($seatIds)) {
                Seat::whereIn('id', $seatIds)->lockForUpdate()->get();
            }

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'transaction_id' => $transactionId,
                'status' => PaymentStatus::SUCCESSFUL,
            ]);

            $booking->update([
                'status' => BookingStatus::PAID,
                'expires_at' => null,
            ]);

            if (!empty($seatIds)) {
                Seat::whereIn('id', $seatIds)->update([
                    'status' => SeatStatus::BOOKED->value,
                ]);
            }

            return $payment->load('booking');
        });
    }
}
