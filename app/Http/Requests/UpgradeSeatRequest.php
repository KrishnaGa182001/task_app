<?php

namespace App\Http\Requests;

use App\Enums\SeatTier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpgradeSeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seat_id' => ['required', 'integer', 'exists:seats,id'],
            'new_tier' => ['required', 'string', Rule::enum(SeatTier::class)],
        ];
    }
}
