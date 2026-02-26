<?php

namespace App\Services\Risk;

class ExposureMatrixService
{
    /**
     * Return the currently active cross-country exposure matrix, or null if
     * none has been configured.
     *
     * Structure when present:
     * [
     *     'countryA' => ['countryB' => 0.6, 'countryC' => 0.2],
     *     'countryB' => ['countryA' => 0.4],
     * ]
     *
     * @return array<string, array<string, float>>|null
     */
    public function getActiveMatrix(): ?array
    {
        return null;
    }
}
