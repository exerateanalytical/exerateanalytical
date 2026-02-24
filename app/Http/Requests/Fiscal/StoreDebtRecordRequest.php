<?php

namespace App\Http\Requests\Fiscal;

use Illuminate\Foundation\Http\FormRequest;

class StoreDebtRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['SuperAdmin', 'CountryAdmin', 'DataAnalyst']) ?? false;
    }

    public function rules(): array
    {
        return [
            'country_id' => 'required|uuid|exists:countries,id',
            'year' => 'required|integer|min:1900|max:2100',
            'total_debt' => 'required|numeric|min:0',
            'debt_to_gdp_ratio' => 'required|numeric|min:0|max:300',
            'external_debt' => 'nullable|numeric|min:0',
            'domestic_debt' => 'nullable|numeric|min:0',
            'debt_service_total' => 'nullable|numeric|min:0',
            'debt_service_ratio' => 'nullable|numeric|min:0|max:100',
            'interest_payments' => 'nullable|numeric|min:0',
            'interest_as_budget_percent' => 'nullable|numeric|min:0|max:100',
            'source_title' => 'required|string|max:255',
            'source_url' => 'required|url',
        ];
    }
}
