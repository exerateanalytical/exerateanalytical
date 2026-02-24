<?php

namespace App\Http\Requests\Country;

use App\Enums\RiskTier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('SuperAdmin');
    }

    public function rules(): array
    {
        $countryId = $this->route('country');

        return [
            'name' => ['required', 'string', 'max:255'],
            'iso_code' => ['required', 'string', 'size:3', 'alpha', Rule::unique('countries', 'iso_code')->ignore($countryId)],
            'continent_region' => ['required', 'string', 'max:255'],
            'risk_tier' => ['required', Rule::enum(RiskTier::class)],
            'is_active' => ['boolean'],
        ];
    }
}
