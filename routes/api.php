<?php

use App\Http\Controllers\AdminSeatController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CancellationController;
use App\Http\Controllers\PaymentController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes for UI demo & inspection
Route::get('/seats', [BookingController::class, 'getSeats']);

Route::post('/demo-token', function (Request $request) {
    $email = $request->input('email', 'user1@example.com');
    $isAdmin = ($email === 'admin@example.com');
    $name = $isAdmin ? 'System Admin' : ($email === 'user2@example.com' ? 'Jane Smith' : 'John Doe');

    $user = User::firstOrCreate(['email' => $email], [
        'name' => $name,
        'password' => bcrypt('password'),
        'is_admin' => $isAdmin,
    ]);

    $token = $user->createToken('demo-token')->plainTextToken;
    return response()->json([
        'token' => $token,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => $user->is_admin,
        ],
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/book', [BookingController::class, 'store']);
    Route::post('/payment', [PaymentController::class, 'store']);
    Route::post('/cancel', [CancellationController::class, 'store']);
    Route::get('/bookings', [BookingController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/admin/seats/upgrade', [AdminSeatController::class, 'upgrade']);
});
