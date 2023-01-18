<?php

namespace App\Exceptions;

use Exception;

//phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousExceptionNaming.SuperfluousSuffix
class AppException extends Exception
{
    public const VALIDATION = 1;

    private $statusCode;

    private $errors;

    public function __construct($message, $code, $statusCode = 500, array $errors = [], Exception $previous = null)
    {
        if (is_numeric($statusCode)) {
            $this->statusCode = $statusCode;
        }

        $this->errors = $errors;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
