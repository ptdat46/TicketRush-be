<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerTicketIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'customer';
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', Rule::in(['valid', 'used', 'expired', 'void'])],
            'sort_by' => ['sometimes', 'required', Rule::in(['issued_at', 'event_starts_at', 'created_at', 'status'])],
            'sort_direction' => ['sometimes', 'required', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}
