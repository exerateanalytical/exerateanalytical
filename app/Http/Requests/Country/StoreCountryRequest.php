<?php

namespace App\Http\Requests\Country;

use App\Enums\RiskTier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('SuperAdmin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'iso_code' => ['required', 'string', 'size:3', 'unique:countries,iso_code', 'alpha'],
            'continent_region' => ['required', 'string', 'max:255'],
            'risk_tier' => ['required', Rule::enum(RiskTier::class)],
            'is_active' => ['boolean'],
        ];
    }
}
