<?php

namespace App\Exceptions;

use Exception;

class RiskTierViolationException extends Exception
{
    public function __construct(string $message = 'Operation not permitted for this risk tier.', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
