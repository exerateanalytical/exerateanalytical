<?php

namespace App\Http\Requests\ExposureMatrix;

use Illuminate\Foundation\Http\FormRequest;

class StoreExposureMatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('SuperAdmin');
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'matrix_json' => ['required', 'array', function (string $attribute, mixed $value, \Closure $fail) {
                foreach ($value as $countryId => $exposures) {
                    if (!is_string($countryId) || !is_array($exposures)) {
                        $fail("Each entry in {$attribute} must map a string country ID to an array of exposures.");
                        return;
                    }

                    foreach ($exposures as $targetId => $weight) {
                        if (!is_string($targetId) || !is_numeric($weight)) {
                            $fail("Each exposure weight in {$attribute}.{$countryId} must be a numeric value keyed by a string country ID.");
                            return;
                        }
                    }
                }
            }],
            'version'    => ['required', 'integer', 'min:1'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ];
    }
}
