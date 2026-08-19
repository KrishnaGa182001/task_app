<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Jobs\ExpireBooking;
use App\Models\Booking;
use Illuminate\Console\Command;

class ExpireBookingsCommand extends Command
{
    protected $signature = 'bookings:expire';
    protected $description = 'Find all expired pending bookings and dispatch expiration jobs';

    public function handle(): int
    {
        $expiredBookingIds = Booking::where('status', BookingStatus::PENDING)
            ->where('expires_at', '<=', now())
            ->pluck('id');

        $count = $expiredBookingIds->count();

        foreach ($expiredBookingIds as $bookingId) {
            ExpireBooking::dispatch($bookingId);
        }

        $this->info("Dispatched ExpireBooking jobs for {$count} expired bookings.");

        return Command::SUCCESS;
    }
}
