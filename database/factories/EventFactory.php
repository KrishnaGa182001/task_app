<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3) . ' Concert',
            'starts_at' => now()->addDays(30),
            'status' => EventStatus::ACTIVE,
        ];
    }
}
