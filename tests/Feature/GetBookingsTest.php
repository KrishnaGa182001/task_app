<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetBookingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_own_bookings(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user);

        $event = Event::factory()->create(['name' => 'Rock Festival 2026']);
        $seat1 = Seat::factory()->create(['event_id' => $event->id, 'seat_no' => 'A14']);
        $seat2 = Seat::factory()->create(['event_id' => $event->id, 'seat_no' => 'A15']);

        $userBooking = Booking::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);
        $userBooking->seats()->attach([$seat1->id, $seat2->id]);

        $otherBooking = Booking::factory()->create([
            'user_id' => $otherUser->id,
            'event_id' => $event->id,
        ]);

        $response = $this->getJson('/api/bookings');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $userBooking->id)
            ->assertJsonPath('data.0.event.name', 'Rock Festival 2026')
            ->assertJsonCount(2, 'data.0.seats');

        // Verify other user's booking is not present in user's response
        $bookingIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($userBooking->id, $bookingIds);
        $this->assertNotContains($otherBooking->id, $bookingIds);
    }
}
