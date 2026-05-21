<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'organizer';
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['sometimes', 'required', 'string', Rule::in(['music', 'dj', 'theater', 'sport', 'workshop', 'conference', 'comedy', 'family', 'other'])],
            'thumbnail_url' => ['nullable', 'string', 'max:2048'],
            'banner_url' => ['nullable', 'string', 'max:2048'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_special' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'venue' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'ticket_sale_starts_at' => ['nullable', 'date'],
            'ticket_sale_ends_at' => ['nullable', 'date', 'after_or_equal:ticket_sale_starts_at'],
            'display_type' => ['sometimes', 'required', Rule::in(['rectangular', 'stadium'])],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'master_width' => ['sometimes', 'required', 'integer', 'min:1', 'max:1000'],
            'master_length' => ['sometimes', 'required', 'integer', 'min:1', 'max:1000'],
            'total_seats' => ['prohibited'],
            'available_seats_count' => ['prohibited'],
        ];
    }
}
