<?php

namespace App\Http\Requests\ExposureMatrix;

use App\Models\Country;
use Illuminate\Foundation\Http\FormRequest;

class StoreExposureMatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('SuperAdmin');
    }

    public function rules(): array
    {
        // Single query; flip gives ['uuid' => index] for O(1) membership tests.
        $validIds = Country::pluck('id')->flip()->all();

        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'matrix_json' => ['required', 'array', function (string $attribute, mixed $value, \Closure $fail) use ($validIds) {
                if (count($value) === 0) {
                    $fail("The {$attribute} must not be empty.");
                    return;
                }

                $hasOutgoing = false;

                foreach ($value as $countryId => $exposures) {
                    if (!is_string($countryId) || !is_array($exposures)) {
                        $fail("Each entry in {$attribute} must map a string country ID to an array of exposures.");
                        continue;
                    }

                    if (!array_key_exists($countryId, $validIds)) {
                        $fail("Country ID \"{$countryId}\" in {$attribute} does not exist.");
                        continue;
                    }

                    foreach ($exposures as $targetId => $weight) {
                        // Mark outgoing as soon as any inner entry exists, before
                        // per-weight checks, to avoid a redundant "no outgoing" failure.
                        $hasOutgoing = true;

                        if (!is_string($targetId)) {
                            $fail("Target IDs under {$attribute}.{$countryId} must be strings.");
                            continue;
                        }

                        if (!array_key_exists($targetId, $validIds)) {
                            $fail("Target country ID \"{$targetId}\" under {$attribute}.{$countryId} does not exist.");
                            continue;
                        }

                        if ($countryId === $targetId) {
                            $fail("Country \"{$countryId}\" in {$attribute} cannot expose to itself.");
                            continue;
                        }

                        if (!is_numeric($weight)) {
                            $fail("Exposure weight at {$attribute}.{$countryId}.{$targetId} must be numeric.");
                            continue;
                        }

                        $float = (float) $weight;

                        if ($float < 0) {
                            $fail("Exposure weight at {$attribute}.{$countryId}.{$targetId} must be >= 0.");
                            continue;
                        }

                        if (!is_finite($float)) {
                            $fail("Exposure weight at {$attribute}.{$countryId}.{$targetId} must be a finite number.");
                        }
                    }
                }

                if (!$hasOutgoing) {
                    $fail("The {$attribute} must contain at least one outgoing exposure.");
                }
            }],
            'version'    => ['required', 'integer', 'min:1'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ];
    }
}
