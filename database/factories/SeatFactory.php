<?php

namespace Database\Factories;

use App\Enums\SeatStatus;
use App\Enums\SeatTier;
use App\Models\Event;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeatFactory extends Factory
{
    protected $model = Seat::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'seat_no' => 'A' . fake()->unique()->numberBetween(1, 1000),
            'status' => SeatStatus::AVAILABLE,
            'tier' => SeatTier::STANDARD,
            'version' => 1,
        ];
    }
}
