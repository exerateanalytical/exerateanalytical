<?php

namespace App\Http\Requests\Institution;

use App\Enums\InstitutionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['SuperAdmin', 'CountryAdmin']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(InstitutionType::class)],
            'legal_mandate' => ['nullable', 'string'],
            'transparency_score' => ['numeric', 'min:0', 'max:100'],
        ];
    }
}
