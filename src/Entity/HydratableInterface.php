<?php

declare(strict_types=1);

namespace Jot\HfRepository\Entity;

/**
 * Interface for entities that can be hydrated with data.
 */
interface HydratableInterface
{
    /**
     * Populates the entity's properties with the provided key-value pairs.
     *
     * @param array $data An associative array where keys represent property names (in snake_case)
     *                    and values are the corresponding values to set.
     * @return self Returns the current instance with the updated properties.
     */
    public function hydrate(array $data): self;
}
