<?php

namespace App\Http\Requests\Governance;

use Illuminate\Foundation\Http\FormRequest;

class StoreIndicatorValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['SuperAdmin', 'CountryAdmin', 'DataAnalyst']) ?? false;
    }

    public function rules(): array
    {
        return [
            'indicator_id' => 'required|uuid|exists:indicators,id',
            'country_id' => 'required|uuid|exists:countries,id',
            'region_id' => 'nullable|uuid|exists:regions,id',
            'year' => 'required|integer|min:1900|max:2100',
            'raw_value' => 'required|numeric',
            'source_title' => 'required|string|max:255',
            'source_url' => 'required|url',
            'source_document' => 'nullable|string|max:255',
        ];
    }
}
