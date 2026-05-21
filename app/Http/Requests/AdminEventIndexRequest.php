<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminEventIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', Rule::in(['pending', 'approved', 'rejected'])],
            'category' => ['sometimes', 'required', 'string'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_special' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'required', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $filters = $this->validated();

        foreach (['is_featured', 'is_special'] as $field) {
            if ($this->has($field)) {
                $filters[$field] = $this->boolean($field);
            }
        }

        return $filters;
    }
}
