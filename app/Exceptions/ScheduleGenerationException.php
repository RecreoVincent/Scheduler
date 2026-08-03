<?php

namespace App\Exceptions;

use RuntimeException;

class ScheduleGenerationException extends RuntimeException
{
    public function __construct(string $message, public readonly string $guidance)
    {
        parent::__construct($message);
    }
}
