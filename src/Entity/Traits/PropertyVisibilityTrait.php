<?php

declare(strict_types=1);

namespace Jot\HfRepository\Entity\Traits;

/**
 * Trait that provides property visibility functionality.
 */
trait PropertyVisibilityTrait
{
    /**
     * List of properties that should be hidden from array representation
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
     * @param string|array $property The property name or an array of property names to hide.
     * @return self The current instance for method chaining.
     */
    public function hide(string|array $property): self
    {
        if (is_array($property)) {
            $this->hiddenProperties = [
                ...$property,
                ...$this->hiddenProperties,
            ];
        } else if (is_string($property)) {
            $this->hiddenProperties[] = $property;
        }

        return $this;
    }

    /**
     * Checks if a property should be hidden.
     *
     * @param string $property The property name to check.
     * @return bool True if the property should be hidden, false otherwise.
     */
    protected function isHidden(string $property): bool
    {
        return in_array($property, $this->hiddenProperties);
    }
}
