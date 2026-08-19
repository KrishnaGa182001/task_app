<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelBookingRequest;
use App\Services\CancellationService;
use Illuminate\Http\JsonResponse;

class CancellationController extends Controller
{
    public function store(CancelBookingRequest $request, CancellationService $cancellationService): JsonResponse
    {
        $booking = $cancellationService->cancelBooking(
            $request->user(),
            (int) $request->validated('booking_id')
        );

        return response()->json([
            'message' => 'Booking cancelled successfully.',
            'booking_id' => $booking->id,
            'status' => $booking->status->value ?? (string) $booking->status,
        ], 200);
    }
}
