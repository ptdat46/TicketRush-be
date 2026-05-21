<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizerEventIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'organizer';
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', Rule::in(['pending', 'approved', 'rejected'])],
            'category' => ['sometimes', 'required', 'string'],
            'starts_after' => ['sometimes', 'required', 'date'],
            'starts_before' => ['sometimes', 'required', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}
