<?php

namespace App\Exceptions;

use Exception;

class ScoreComputationException extends Exception
{
    public function __construct(string $message = 'Score computation failed.', int $code = 500)
    {
        parent::__construct($message, $code);
    }
}
