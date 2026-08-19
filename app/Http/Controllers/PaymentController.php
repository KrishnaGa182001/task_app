<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function store(PaymentRequest $request, PaymentService $paymentService): JsonResponse
    {
        $payment = $paymentService->processPayment(
            $request->user(),
            (int) $request->validated('booking_id'),
            $request->validated('transaction_id')
        );

        return response()->json([
            'message' => 'Payment processed successfully.',
            'payment_id' => $payment->id,
            'booking_id' => $payment->booking_id,
            'transaction_id' => $payment->transaction_id,
            'status' => $payment->status->value ?? (string) $payment->status,
        ], 200);
    }
}
