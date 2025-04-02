<?php

declare(strict_types=1);

namespace Jot\HfRepository\Exception;

class RecordNotFoundException extends \Exception
{
    public function __construct(int $code = 0, \Throwable $previous = null)
    {
        $message = __('hf-repository.record_not_found');
        parent::__construct($message, $code, $previous);
    }
}