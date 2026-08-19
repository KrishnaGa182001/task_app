<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\SeatStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Seat;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = new PaymentService();
    }

    public function test_duplicate_payment_transaction_id_is_idempotent(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $seat = Seat::factory()->create(['event_id' => $event->id, 'status' => SeatStatus::RESERVED]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => BookingStatus::PENDING,
            'expires_at' => now()->addMinutes(10),
        ]);
        $booking->seats()->attach($seat->id);

        $transactionId = 'CHG-998877';

        // First payment call
        $payment1 = $this->paymentService->processPayment($user, $booking->id, $transactionId);

        // Second payment call with duplicate transaction_id
        $payment2 = $this->paymentService->processPayment($user, $booking->id, $transactionId);

        // Third payment call
        $payment3 = $this->paymentService->processPayment($user, $booking->id, $transactionId);

        $this->assertEquals($payment1->id, $payment2->id);
        $this->assertEquals($payment1->id, $payment3->id);

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', [
            'transaction_id' => $transactionId,
            'booking_id' => $booking->id,
            'status' => PaymentStatus::SUCCESSFUL->value,
        ]);

        $this->assertEquals(BookingStatus::PAID, $booking->fresh()->status);
        $this->assertEquals(SeatStatus::BOOKED, $seat->fresh()->status);
    }

    public function test_api_payment_endpoint_returns_idempotent_response(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $event = Event::factory()->create();
        $seat = Seat::factory()->create(['event_id' => $event->id, 'status' => SeatStatus::RESERVED]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => BookingStatus::PENDING,
            'expires_at' => now()->addMinutes(10),
        ]);
        $booking->seats()->attach($seat->id);

        $payload = [
            'booking_id' => $booking->id,
            'transaction_id' => 'CHG-REPEAT-001',
        ];

        // Request 1
        $response1 = $this->postJson('/api/payment', $payload);
        $response1->assertStatus(200);

        // Request 2 (Duplicate)
        $response2 = $this->postJson('/api/payment', $payload);
        $response2->assertStatus(200);

        // Both responses should contain identical payment_id
        $this->assertEquals($response1->json('payment_id'), $response2->json('payment_id'));

        $this->assertDatabaseCount('payments', 1);
    }
}
