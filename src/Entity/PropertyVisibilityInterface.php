<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
 * @license  MIT
 */

namespace Jot\HfRepository\Entity;

/**
 * Interface for entities that can control property visibility.
 */
interface PropertyVisibilityInterface
{
    /**
     * Hides the specified property or properties by adding them to a list of hidden properties.
     *
     * @param array|string $property the property name or an array of property names to hide
     * @return self the current instance for method chaining
     */
    public function hide(array|string $property): self;
}
