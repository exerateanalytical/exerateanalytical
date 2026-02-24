<?php

namespace App\Exceptions;

use Exception;

class DataIntegrityException extends Exception
{
    public function __construct(string $message = 'Data integrity violation.', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
