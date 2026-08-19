<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class SeatUnavailableException extends Exception
{
    public function __construct(string $message = 'One or more requested seats are unavailable.', int $code = 409)
    {
        parent::__construct($message, $code);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], $this->getCode() ?: 409);
    }
}
