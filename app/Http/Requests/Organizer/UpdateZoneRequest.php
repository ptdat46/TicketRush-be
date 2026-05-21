<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'organizer';
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'color' => ['sometimes', 'required', 'string', 'max:50'],
            'icon_url' => ['nullable', 'string', 'max:2048'],
            'pos_x' => ['sometimes', 'required', 'integer', 'min:0'],
            'pos_y' => ['sometimes', 'required', 'integer', 'min:0'],
            'width' => ['sometimes', 'required', 'integer', 'min:1', 'max:1000'],
            'length' => ['sometimes', 'required', 'integer', 'min:1', 'max:1000'],
            'is_seating' => ['sometimes', 'boolean'],
        ];
    }
}
