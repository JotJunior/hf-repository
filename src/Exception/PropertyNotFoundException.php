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
use function Hyperf\Translation\__;

class PropertyNotFoundException extends Exception
{
    public function __construct(string $property, int $code = 0, ?Throwable $previous = null)
    {
        $message = __('hf-repository.property_not_found', ['property' => $property]);
        parent::__construct($message, $code, $previous);
    }
}
