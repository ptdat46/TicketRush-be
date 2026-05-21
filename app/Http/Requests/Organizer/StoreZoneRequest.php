<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;

class StoreZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'organizer';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'color' => ['required', 'string', 'max:50'],
            'icon_url' => ['nullable', 'string', 'max:2048'],
            'pos_x' => ['required', 'integer', 'min:0'],
            'pos_y' => ['required', 'integer', 'min:0'],
            'width' => ['required', 'integer', 'min:1', 'max:1000'],
            'length' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_seating' => ['sometimes', 'boolean'],
        ];
    }
}
