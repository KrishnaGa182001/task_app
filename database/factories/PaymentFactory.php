<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'transaction_id' => 'CHG-' . fake()->unique()->alphanumeric(8),
            'status' => PaymentStatus::SUCCESSFUL,
        ];
    }
}
