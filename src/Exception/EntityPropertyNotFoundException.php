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

use RuntimeException;
use Throwable;

use function Hyperf\Translation\__;

class EntityPropertyNotFoundException extends RuntimeException
{
    protected $code = 400;

    public function __construct(string $property, int $code = 0, ?Throwable $previous = null)
    {
        $code = $code ?: $this->code;
        $message = __('hf-repository.property_not_found', ['property' => $property]);
        parent::__construct($message, $code, $previous);
    }
}
