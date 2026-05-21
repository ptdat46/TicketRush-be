<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutSeatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'customer';
    }

    public function rules(): array
    {
        return [
            'seat_ids' => ['required', 'array', 'min:1', 'max:10'],
            'seat_ids.*' => ['required', 'integer', 'distinct', 'exists:seats,id'],
            'payment_method' => ['sometimes', 'required', Rule::in(['mock'])],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
