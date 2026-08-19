<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\SeatStatus;
use App\Enums\SeatTier;
use App\Models\Event;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Admin User
        $admin = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'System Admin',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        // 2. Create Regular Test Users
        $user1 = User::firstOrCreate([
            'email' => 'user1@example.com',
        ], [
            'name' => 'John Doe',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $user2 = User::firstOrCreate([
            'email' => 'user2@example.com',
        ], [
            'name' => 'Jane Smith',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        // 3. Create 1 Event
        $event = Event::firstOrCreate([
            'name' => 'Grand World Tour Concert 2026',
        ], [
            'starts_at' => now()->addDays(30),
            'status' => EventStatus::ACTIVE,
        ]);

        // 4. Create 100 Seats (10 VIP, 90 Standard) - All default to AVAILABLE
        // 10 VIP Seats (VIP-01 to VIP-10)
        for ($i = 1; $i <= 10; $i++) {
            Seat::firstOrCreate([
                'event_id' => $event->id,
                'seat_no' => 'VIP-' . sprintf('%02d', $i),
            ], [
                'tier' => SeatTier::VIP,
                'status' => SeatStatus::AVAILABLE,
                'version' => 1,
            ]);
        }

        // 90 Standard Seats (STD-001 to STD-090)
        for ($i = 1; $i <= 90; $i++) {
            Seat::firstOrCreate([
                'event_id' => $event->id,
                'seat_no' => 'STD-' . sprintf('%03d', $i),
            ], [
                'tier' => SeatTier::STANDARD,
                'status' => SeatStatus::AVAILABLE,
                'version' => 1,
            ]);
        }
    }
}
