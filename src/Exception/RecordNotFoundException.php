<?php

declare(strict_types=1);
/**
 * This file is part of hf-repository
 *
 * @link     https://github.com/JotJunior/hf-repository
 * @contact  hf-repository@jot.com.br
 * @license  MIT
 */

namespace Jot\HfRepository\Exception;

use Exception;
use Throwable;

class RecordNotFoundException extends Exception
{
    public function __construct(int $code = 0, ?Throwable $previous = null)
    {
        $message = __('hf-repository.record_not_found');
        parent::__construct($message, $code, $previous);
    }
}
