<?php

namespace App\Http\Requests\Civic;

use Illuminate\Foundation\Http\FormRequest;

class StorePetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public submission
    }

    public function rules(): array
    {
        return [
            'country_id' => 'required|uuid|exists:countries,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:50',
            'category' => 'nullable|string|max:100',
        ];
    }
}
