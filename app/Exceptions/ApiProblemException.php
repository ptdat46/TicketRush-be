<?php

namespace App\Exceptions;

use RuntimeException;

class ApiProblemException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 422,
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
