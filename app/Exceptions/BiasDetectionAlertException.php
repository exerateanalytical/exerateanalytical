<?php

namespace App\Exceptions;

use Exception;

class BiasDetectionAlertException extends Exception
{
    public function __construct(string $message = 'Potential methodological bias detected.', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
