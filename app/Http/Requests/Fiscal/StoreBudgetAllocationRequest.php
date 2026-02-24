<?php

namespace App\Http\Requests\Fiscal;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['SuperAdmin', 'CountryAdmin', 'DataAnalyst']) ?? false;
    }

    public function rules(): array
    {
        return [
            'country_id' => 'required|uuid|exists:countries,id',
            'region_id' => 'nullable|uuid|exists:regions,id',
            'year' => 'required|integer|min:1900|max:2100',
            'sector_name' => 'required|string|max:255',
            'allocated_amount' => 'required|numeric|min:0',
            'executed_amount' => 'nullable|numeric|min:0',
            'source_title' => 'required|string|max:255',
            'source_url' => 'required|url',
        ];
    }
}
