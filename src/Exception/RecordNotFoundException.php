<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for
 * manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
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
