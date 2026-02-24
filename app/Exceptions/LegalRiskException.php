<?php

namespace App\Exceptions;

use Exception;

class LegalRiskException extends Exception
{
    public function __construct(string $message = 'A legal risk has been detected.', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
