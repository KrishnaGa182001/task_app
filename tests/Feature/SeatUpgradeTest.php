<?php

namespace Tests\Feature;

use App\Enums\SeatTier;
use App\Models\Event;
use App\Models\Seat;
use App\Models\User;
use App\Services\SeatUpgradeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SeatUpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upgrade_seat_tier_and_generate_audit_log(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->create();
        $seat = Seat::factory()->create([
            'event_id' => $event->id,
            'tier' => SeatTier::STANDARD,
        ]);

        $service = new SeatUpgradeService();
        $upgradedSeat = $service->upgradeSeat($admin, $seat->id, 'vip');

        $this->assertEquals(SeatTier::VIP, $upgradedSeat->tier);

        $this->assertDatabaseHas('seats', [
            'id' => $seat->id,
            'tier' => SeatTier::VIP->value,
        ]);

        $this->assertDatabaseHas('seat_audit_logs', [
            'seat_id' => $seat->id,
            'old_tier' => SeatTier::STANDARD->value,
            'new_tier' => SeatTier::VIP->value,
            'admin_id' => $admin->id,
        ]);
    }

    public function test_non_admin_cannot_upgrade_seat_tier_via_api(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        Sanctum::actingAs($user);

        $event = Event::factory()->create();
        $seat = Seat::factory()->create(['event_id' => $event->id, 'tier' => SeatTier::STANDARD]);

        $response = $this->postJson('/api/admin/seats/upgrade', [
            'seat_id' => $seat->id,
            'new_tier' => 'vip',
        ]);

        $response->assertStatus(403);
        $this->assertEquals(SeatTier::STANDARD, $seat->fresh()->tier);
    }
}
