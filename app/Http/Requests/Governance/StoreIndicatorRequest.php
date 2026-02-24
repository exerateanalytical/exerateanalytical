<?php

namespace App\Http\Requests\Governance;

use Illuminate\Foundation\Http\FormRequest;

class StoreIndicatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['SuperAdmin', 'CountryAdmin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'pillar_id' => 'required|uuid|exists:pillars,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'nullable|string|max:50',
            'weight' => 'required|numeric|min:0|max:100',
            'normalization_method' => 'required|in:minmax,zscore,inverse_minmax',
            'data_type' => 'required|in:numeric,percentage,ratio,index',
            'reliability_level' => 'required|in:high,moderate,limited',
            'reporting_lag_months' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ];
    }
}
