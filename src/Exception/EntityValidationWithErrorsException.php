<?php

namespace Jot\HfRepository\Exception;

class EntityValidationWithErrorsException extends \Exception
{

    protected $message = 'Validation errors:';
    protected array $errors;

    public function __construct(array $errors, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($this->message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

}