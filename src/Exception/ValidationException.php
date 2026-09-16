<?php

namespace App\Exception;

use Exception;

class ValidationException extends Exception
{
    public function __construct(
        private array $errors
    ) {
        parent::__construct('Dữ liệu không hợp lệ.');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}