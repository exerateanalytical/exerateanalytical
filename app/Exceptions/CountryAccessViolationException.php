<?php

namespace App\Exceptions;

use Exception;

class CountryAccessViolationException extends Exception
{
    public function __construct(string $message = 'Unauthorized cross-country data access attempt.', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
