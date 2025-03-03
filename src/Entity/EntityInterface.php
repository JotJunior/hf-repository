<?php

declare(strict_types=1);

namespace Jot\HfRepository\Entity;

/**
 * Core interface for all entities in the system.
 */
interface EntityInterface
{
    public function __get(string $name): mixed;
}
