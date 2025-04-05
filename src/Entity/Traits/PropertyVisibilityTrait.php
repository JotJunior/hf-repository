<?php

declare(strict_types=1);
/**
 * This file is part of hf-repository
 *
 * @link     https://github.com/JotJunior/hf-repository
 * @contact  hf-repository@jot.com.br
 * @license  MIT
 */

namespace Jot\HfRepository\Entity\Traits;

/**
 * Trait that provides property visibility functionality.
 */
trait PropertyVisibilityTrait
{
    /**
     * List of properties that should be hidden from array representation.
     */
    protected array $hiddenProperties = [
        '@timestamp',
        'deleted',
        'entity_factory',
        'entity_factory_class',
        'entity_state',
        'errors',
        'event_dispatcher',
        'hidden_properties',
        'logger',
        'validators',
    ];

    /**
     * Hides the specified property or properties by adding them to a list of hidden properties.
     *
     * @param array|string $property the property name or an array of property names to hide
     * @return self the current instance for method chaining
     */
    public function hide(array|string $property): self
    {
        if (is_array($property)) {
            $this->hiddenProperties = [
                ...$property,
                ...$this->hiddenProperties,
            ];
        } elseif (is_string($property)) {
            $this->hiddenProperties[] = $property;
        }

        return $this;
    }

    /**
     * Checks if a property should be hidden.
     *
     * @param string $property the property name to check
     * @return bool true if the property should be hidden, false otherwise
     */
    protected function isHidden(string $property): bool
    {
        return in_array($property, $this->hiddenProperties);
    }
}
