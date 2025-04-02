<?php

declare(strict_types=1);

namespace Jot\HfRepository\Exception;

class EntityValidationWithErrorsException extends \Exception
{
    protected array $errors;

    public function __construct(array $errors, int $code = 0, \Throwable $previous = null)
    {
        $message = __('hf-repository.validation_errors');
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}