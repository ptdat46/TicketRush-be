<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicEventIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['sometimes', 'required', 'string', Rule::in(['music', 'dj', 'theater', 'sport', 'workshop', 'conference', 'comedy', 'family', 'other'])],
            'q' => ['sometimes', 'required', 'string', 'max:255'],
            'starts_after' => ['sometimes', 'required', 'date'],
            'starts_before' => ['sometimes', 'required', 'date'],
            'sale_starts_after' => ['sometimes', 'required', 'date'],
            'sale_starts_before' => ['sometimes', 'required', 'date'],
            'ticket_status' => ['sometimes', 'required', Rule::in(['on_sale', 'sold_out', 'not_started', 'ended'])],
            'is_featured' => ['sometimes', 'boolean'],
            'is_special' => ['sometimes', 'boolean'],
            'trending' => ['sometimes', 'boolean'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $data = $this->validated();

        foreach (['is_featured', 'is_special', 'trending'] as $field) {
            if ($this->has($field)) {
                $data[$field] = $this->boolean($field);
            }
        }

        return $data;
    }
}
