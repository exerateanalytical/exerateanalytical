<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedCountryAccessException extends Exception
{
    public function __construct(string $message = 'You are not authorized to access this country data.', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
