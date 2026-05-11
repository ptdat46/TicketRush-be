<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
        ];

        if ($this->user()->role === 'organizer') {
            $rules = array_merge($rules, [
                'organizer_name' => ['sometimes', 'required', 'string', 'max:255'],
                'tax_code' => ['sometimes', 'required', 'string', 'max:50'],
            ]);
        }

        return $rules;
    }
}
