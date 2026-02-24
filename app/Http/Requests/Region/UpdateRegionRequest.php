<?php

namespace App\Http\Requests\Region;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['SuperAdmin', 'CountryAdmin']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'administrative_level' => ['required', 'integer', 'min:1', 'max:10'],
            'population' => ['required', 'integer', 'min:0'],
            'area_km2' => ['required', 'numeric', 'min:0'],
            'parent_region_id' => ['nullable', 'uuid', 'exists:regions,id'],
        ];
    }
}
