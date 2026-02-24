<?php

namespace App\Exceptions;

use Exception;

class IndicatorWeightMismatchException extends Exception
{
    public function __construct(string $message = 'Indicator weights do not sum to 100%.', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
