<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
 * @license  MIT
 */

namespace Jot\HfRepository\Exception;

use Exception;
use Throwable;

class EntityValidationWithErrorsException extends Exception
{
    protected array $errors;

    public function __construct(array $errors, int $code = 0, ?Throwable $previous = null)
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
