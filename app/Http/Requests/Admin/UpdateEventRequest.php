<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
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
            'status' => ['sometimes', 'required', Rule::in(['pending', 'approved', 'rejected'])],
            'display_type' => ['prohibited'],
            'master_width' => ['prohibited'],
            'master_length' => ['prohibited'],
            'total_seats' => ['prohibited'],
            'available_seats_count' => ['prohibited'],
            'bank_name' => ['prohibited'],
            'bank_account_number' => ['prohibited'],
            'bank_account_name' => ['prohibited'],
            'zones' => ['prohibited'],
            'zone_count' => ['prohibited'],
            'zones_count' => ['prohibited'],
            'zone_prices' => ['prohibited'],
        ];
    }
}
