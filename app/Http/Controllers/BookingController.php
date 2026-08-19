<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\SeatStatus;
use App\Enums\SeatTier;
use App\Http\Requests\BookSeatsRequest;
use App\Jobs\ExpireBooking;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Seat;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function store(BookSeatsRequest $request, BookingService $bookingService): JsonResponse
    {
        $booking = $bookingService->bookSeats(
            $request->user(),
            (int) $request->validated('event_id'),
            $request->validated('seat_ids')
        );

        return response()->json([
            'booking_id' => $booking->id,
            'expires_at' => $booking->expires_at?->toIso8601String(),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $bookings = $request->user()->bookings()
            ->with([
                'event:id,name',
                'seats:id,seat_no,tier',
            ])
            ->latest()
            ->paginate(15);

        return response()->json($bookings);
    }

    public function getSeats(): JsonResponse
    {
        $event = Event::first();
        if (!$event) {
            $event = Event::create([
                'name' => 'Grand World Tour Concert 2026',
                'starts_at' => now()->addDays(30),
                'status' => EventStatus::ACTIVE,
            ]);
        }

        $expiredBookingIds = Booking::where('status', BookingStatus::PENDING)
            ->where('expires_at', '<=', now())
            ->pluck('id');

        foreach ($expiredBookingIds as $id) {
            (new ExpireBooking($id))->handle();
        }

        if (Seat::where('event_id', $event->id)->count() === 0) {
            for ($i = 1; $i <= 10; $i++) {
                Seat::create([
                    'event_id' => $event->id,
                    'seat_no' => 'VIP-' . sprintf('%02d', $i),
                    'tier' => SeatTier::VIP,
                    'status' => SeatStatus::AVAILABLE,
                    'version' => 1,
                ]);
            }
            for ($i = 1; $i <= 90; $i++) {
                Seat::create([
                    'event_id' => $event->id,
                    'seat_no' => 'STD-' . sprintf('%03d', $i),
                    'tier' => SeatTier::STANDARD,
                    'status' => SeatStatus::AVAILABLE,
                    'version' => 1,
                ]);
            }
        }

        $seats = Seat::where('event_id', $event->id)
            ->with(['bookings' => function ($query) {
                $query->latest()->with('user:id,name,email');
            }])
            ->orderBy('id')
            ->get()
            ->map(function ($seat) {
                $latestBooking = $seat->bookings->first();
                return [
                    'id' => $seat->id,
                    'event_id' => $seat->event_id,
                    'seat_no' => $seat->seat_no,
                    'status' => $seat->status->value ?? (string) $seat->status,
                    'tier' => $seat->tier->value ?? (string) $seat->tier,
                    'version' => $seat->version,
                    'active_booking_id' => $latestBooking?->id,
                    'booking_status' => $latestBooking?->status->value ?? (string) $latestBooking?->status,
                    'owner_user_id' => $latestBooking?->user_id,
                    'owner_user_name' => $latestBooking?->user?->name,
                    'owner_user_email' => $latestBooking?->user?->email,
                ];
            });

        return response()->json([
            'event' => $event,
            'seats' => $seats,
        ]);
    }
}
