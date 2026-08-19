<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\SeatStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ConcurrentReservationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_100_concurrent_users_competing_for_5_seats(): void
    {
        $event = Event::factory()->create(['name' => 'High Concurrency Concert']);
        $seats = [];
        for ($i = 1; $i <= 5; $i++) {
            $seats[] = Seat::factory()->create([
                'event_id' => $event->id,
                'seat_no' => 'CONC-' . $i,
                'status' => SeatStatus::AVAILABLE,
            ]);
        }

        $seatIds = array_map(fn ($s) => $s->id, $seats);
        $seatIdsJson = json_encode($seatIds);

        $users = User::factory()->count(100)->create();

        $processes = [];
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $artisanPath = base_path('artisan');
        $phpBinary = PHP_BINARY;

        foreach ($users as $user) {
            $cmd = sprintf(
                '"%s" "%s" app:concurrent-book-worker %d %d "%s"',
                $phpBinary,
                $artisanPath,
                $user->id,
                $event->id,
                addslashes($seatIdsJson)
            );

            $proc = proc_open($cmd, $descriptors, $pipes);
            if (is_resource($proc)) {
                $processes[] = [
                    'proc' => $proc,
                    'pipes' => $pipes,
                ];
            }
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($processes as $item) {
            $stdout = stream_get_contents($item['pipes'][1]);
            fclose($item['pipes'][1]);
            fclose($item['pipes'][2]);

            $exitCode = proc_close($item['proc']);

            if ($exitCode === 0 && str_contains($stdout, 'SUCCESS')) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        $this->assertEquals(1, $successCount);
        $this->assertEquals(99, $failureCount);

        $this->assertDatabaseCount('bookings', 1);

        $booking = Booking::first();
        $this->assertEquals(BookingStatus::PENDING, $booking->status);

        $this->assertDatabaseCount('booking_seats', 5);
        $this->assertEquals(5, Seat::where('event_id', $event->id)->where('status', SeatStatus::RESERVED)->count());
    }

    public function test_100_concurrent_users_competing_for_single_individual_seats(): void
    {
        $event = Event::factory()->create(['name' => 'Individual Seats Concert']);
        $seats = [];
        for ($i = 1; $i <= 5; $i++) {
            $seats[] = Seat::factory()->create([
                'event_id' => $event->id,
                'seat_no' => 'IND-' . $i,
                'status' => SeatStatus::AVAILABLE,
            ]);
        }

        $seatIds = array_map(fn ($s) => $s->id, $seats);

        $users = User::factory()->count(100)->create();

        $processes = [];
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $artisanPath = base_path('artisan');
        $phpBinary = PHP_BINARY;

        foreach ($users as $index => $user) {
            $targetSeatId = $seatIds[$index % 5];
            $seatIdsJson = json_encode([$targetSeatId]);

            $cmd = sprintf(
                '"%s" "%s" app:concurrent-book-worker %d %d "%s"',
                $phpBinary,
                $artisanPath,
                $user->id,
                $event->id,
                addslashes($seatIdsJson)
            );

            $proc = proc_open($cmd, $descriptors, $pipes);
            if (is_resource($proc)) {
                $processes[] = [
                    'proc' => $proc,
                    'pipes' => $pipes,
                ];
            }
        }

        $successCount = 0;

        foreach ($processes as $item) {
            $stdout = stream_get_contents($item['pipes'][1]);
            fclose($item['pipes'][1]);
            fclose($item['pipes'][2]);

            $exitCode = proc_close($item['proc']);
            if ($exitCode === 0 && str_contains($stdout, 'SUCCESS')) {
                $successCount++;
            }
        }

        $this->assertEquals(5, $successCount);
        $this->assertEquals(5, Seat::where('event_id', $event->id)->where('status', SeatStatus::RESERVED)->count());
        $this->assertDatabaseCount('bookings', 5);
    }
}
