<?php

declare(strict_types=1);

namespace Jot\HfRepository\Exception;

class InvalidEntityException extends \Exception
{
    public function __construct(int $code = 0, \Throwable $previous = null)
    {
        $message = __('hf-repository.invalid_entity');
        parent::__construct($message, $code, $previous);
    }
}