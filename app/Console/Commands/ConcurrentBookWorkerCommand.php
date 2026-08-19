<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BookingService;
use Illuminate\Console\Command;
use Throwable;

class ConcurrentBookWorkerCommand extends Command
{
    protected $signature = 'app:concurrent-book-worker {userId} {eventId} {seatIds}';
    protected $description = 'Worker command for concurrent booking test';

    public function handle(BookingService $bookingService): int
    {
        $userId = (int) $this->argument('userId');
        $eventId = (int) $this->argument('eventId');
        $seatIds = json_decode($this->argument('seatIds'), true);

        $user = User::find($userId);
        if (!$user) {
            $this->error('USER_NOT_FOUND');
            return 1;
        }

        try {
            $booking = $bookingService->bookSeats($user, $eventId, $seatIds);
            $this->info("SUCCESS:{$booking->id}");
            return 0;
        } catch (Throwable $e) {
            $this->error("FAILED:{$e->getMessage()}");
            return 1;
        }
    }
}
