<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\SeatStatus;
use App\Exceptions\InvalidBookingStateException;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Seat;
use App\Models\User;
use App\Services\CancellationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CancellationTest extends TestCase
{
    use RefreshDatabase;

    private CancellationService $cancellationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cancellationService = new CancellationService();
    }

    public function test_cancelling_pending_booking_releases_reserved_seats(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $seat = Seat::factory()->create(['event_id' => $event->id, 'status' => SeatStatus::RESERVED]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => BookingStatus::PENDING,
        ]);
        $booking->seats()->attach($seat->id);

        $result = $this->cancellationService->cancelBooking($user, $booking->id);

        $this->assertEquals(BookingStatus::CANCELLED, $result->status);
        $this->assertEquals(SeatStatus::AVAILABLE, $seat->fresh()->status);
    }

    public function test_cancelling_paid_booking_throws_invalid_state_exception(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $seat = Seat::factory()->create(['event_id' => $event->id, 'status' => SeatStatus::BOOKED]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => BookingStatus::PAID,
        ]);
        $booking->seats()->attach($seat->id);

        $this->expectException(InvalidBookingStateException::class);

        $this->cancellationService->cancelBooking($user, $booking->id);
    }

    public function test_user_cannot_cancel_another_users_booking(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $event = Event::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => $owner->id,
            'event_id' => $event->id,
            'status' => BookingStatus::PENDING,
        ]);

        $this->expectException(AuthorizationException::class);

        $this->cancellationService->cancelBooking($attacker, $booking->id);
    }

    public function test_api_cancel_endpoint(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $event = Event::factory()->create();
        $seat = Seat::factory()->create(['event_id' => $event->id, 'status' => SeatStatus::RESERVED]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => BookingStatus::PENDING,
        ]);
        $booking->seats()->attach($seat->id);

        $response = $this->postJson('/api/cancel', [
            'booking_id' => $booking->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Booking cancelled successfully.',
                'status' => 'cancelled',
            ]);

        $this->assertEquals(SeatStatus::AVAILABLE, $seat->fresh()->status);
    }
}
