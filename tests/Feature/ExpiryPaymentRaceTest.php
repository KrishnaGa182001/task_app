<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\SeatStatus;
use App\Exceptions\BookingExpiredException;
use App\Jobs\ExpireBooking;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Seat;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpiryPaymentRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiry_vs_payment_race_condition_maintains_data_integrity(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $seat = Seat::factory()->create([
            'event_id' => $event->id,
            'status' => SeatStatus::RESERVED,
        ]);

        // Create booking that is right at expiration time
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => BookingStatus::PENDING,
            'expires_at' => now()->subSecond(),
        ]);
        $booking->seats()->attach($seat->id);

        $paymentService = new PaymentService();

        // Scenario 1: Expiry Job runs first
        $job = new ExpireBooking($booking->id);
        $job->handle();

        // Payment attempt after expiry
        try {
            $paymentService->processPayment($user, $booking->id, 'CHG-RACE-001');
        } catch (BookingExpiredException $e) {
            // Expected rejection
        }

        $freshBooking = $booking->fresh();
        $freshSeat = $seat->fresh();

        $this->assertEquals(BookingStatus::EXPIRED, $freshBooking->status);
        $this->assertEquals(SeatStatus::AVAILABLE, $freshSeat->status);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_wins_race_over_expiry_job(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $seat = Seat::factory()->create([
            'event_id' => $event->id,
            'status' => SeatStatus::RESERVED,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => BookingStatus::PENDING,
            'expires_at' => now()->addSecond(), // just before expiration
        ]);
        $booking->seats()->attach($seat->id);

        $paymentService = new PaymentService();

        // Payment processes successfully first
        $payment = $paymentService->processPayment($user, $booking->id, 'CHG-RACE-002');
        $this->assertNotNull($payment);

        // Expiry Job runs afterwards
        $job = new ExpireBooking($booking->id);
        $job->handle();

        $freshBooking = $booking->fresh();
        $freshSeat = $seat->fresh();

        // Verify state is paid + booked (Expiry job skipped)
        $this->assertEquals(BookingStatus::PAID, $freshBooking->status);
        $this->assertEquals(SeatStatus::BOOKED, $freshSeat->status);
    }
}
