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

namespace Jot\HfRepository\Entity;

/**
 * Interface for entities that can be hydrated with data.
 */
interface HydratableInterface
{
    /**
     * Populates the entity's properties with the provided key-value pairs.
     *
     * @param array $data an associative array where keys represent property names (in snake_case)
     *                    and values are the corresponding values to set
     * @return self returns the current instance with the updated properties
     */
    public function hydrate(array $data): self;
}
