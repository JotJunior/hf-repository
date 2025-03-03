<?php

declare(strict_types=1);

namespace Jot\HfRepository\Entity;

/**
 * Interface for entities that can control property visibility.
 */
interface PropertyVisibilityInterface
{
    /**
     * Hides the specified property or properties by adding them to a list of hidden properties.
     *
     * @param string|array $property The property name or an array of property names to hide.
     * @return self The current instance for method chaining.
     */
    public function hide(string|array $property): self;
}
