<?php

namespace App\Http\Requests\Development;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceAccessRequest extends FormRequest
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
            'electricity_access_percent' => 'nullable|numeric|min:0|max:100',
            'safe_water_access_percent' => 'nullable|numeric|min:0|max:100',
            'road_density_km_per_100km2' => 'nullable|numeric|min:0',
            'paved_road_percent' => 'nullable|numeric|min:0|max:100',
            'healthcare_facilities_total' => 'nullable|integer|min:0',
            'schools_total' => 'nullable|integer|min:0',
            'internet_penetration_percent' => 'nullable|numeric|min:0|max:100',
            'mobile_network_coverage_percent' => 'nullable|numeric|min:0|max:100',
            'source_title' => 'required|string|max:255',
            'source_url' => 'required|url',
        ];
    }
}
