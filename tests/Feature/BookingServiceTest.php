<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\SeatStatus;
use App\Exceptions\SeatUnavailableException;
use App\Models\Event;
use App\Models\Seat;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = new BookingService();
    }

    public function test_successful_seat_reservation(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $seats = Seat::factory()->count(3)->create([
            'event_id' => $event->id,
            'status' => SeatStatus::AVAILABLE,
        ]);

        $seatIds = $seats->pluck('id')->toArray();

        $booking = $this->bookingService->bookSeats($user, $event->id, $seatIds);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => BookingStatus::PENDING->value,
        ]);

        $this->assertCount(3, $booking->seats);

        foreach ($seatIds as $seatId) {
            $this->assertDatabaseHas('seats', [
                'id' => $seatId,
                'status' => SeatStatus::RESERVED->value,
            ]);
        }
    }

    public function test_partial_availability_causes_entire_booking_to_fail(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $seat1 = Seat::factory()->create(['event_id' => $event->id, 'status' => SeatStatus::AVAILABLE]);
        $seat2 = Seat::factory()->create(['event_id' => $event->id, 'status' => SeatStatus::AVAILABLE]);
        $seat3 = Seat::factory()->create(['event_id' => $event->id, 'status' => SeatStatus::BOOKED]);

        $this->expectException(SeatUnavailableException::class);

        try {
            $this->bookingService->bookSeats($user, $event->id, [$seat1->id, $seat2->id, $seat3->id]);
        } finally {
            // Verify NO partial reservation occurred (atomic rollback)
            $this->assertEquals(SeatStatus::AVAILABLE, $seat1->fresh()->status);
            $this->assertEquals(SeatStatus::AVAILABLE, $seat2->fresh()->status);
            $this->assertEquals(SeatStatus::BOOKED, $seat3->fresh()->status);

            $this->assertDatabaseCount('bookings', 0);
            $this->assertDatabaseCount('booking_seats', 0);
        }
    }

    public function test_booking_fails_if_seat_does_not_belong_to_event(): void
    {
        $user = User::factory()->create();
        $event1 = Event::factory()->create();
        $event2 = Event::factory()->create();

        $seat1 = Seat::factory()->create(['event_id' => $event1->id, 'status' => SeatStatus::AVAILABLE]);
        $seat2 = Seat::factory()->create(['event_id' => $event2->id, 'status' => SeatStatus::AVAILABLE]);

        $this->expectException(SeatUnavailableException::class);

        $this->bookingService->bookSeats($user, $event1->id, [$seat1->id, $seat2->id]);
    }
}
