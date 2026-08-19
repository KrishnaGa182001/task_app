<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpgradeSeatRequest;
use App\Services\SeatUpgradeService;
use Illuminate\Http\JsonResponse;

class AdminSeatController extends Controller
{
    public function upgrade(UpgradeSeatRequest $request, SeatUpgradeService $seatUpgradeService): JsonResponse
    {
        $seat = $seatUpgradeService->upgradeSeat(
            $request->user(),
            (int) $request->validated('seat_id'),
            $request->validated('new_tier')
        );

        return response()->json([
            'message' => 'Seat tier upgraded successfully.',
            'seat_id' => $seat->id,
            'seat_no' => $seat->seat_no,
            'tier' => $seat->tier->value ?? (string) $seat->tier,
        ], 200);
    }
}
