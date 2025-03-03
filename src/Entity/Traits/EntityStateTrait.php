<?php

declare(strict_types=1);

namespace Jot\HfRepository\Entity\Traits;

use Jot\HfRepository\Exception\EntityValidationWithErrorsException;

/**
 * Trait that provides entity state functionality.
 */
trait EntityStateTrait
{
    /**
     * State constants
     */
    public const STATE_CREATE = 'create';
    public const STATE_UPDATE = 'update';
    
    /**
     * The current entity state
     */
    private string $entityState = self::STATE_CREATE;
    
    /**
     * Sets the current state of the entity.
     *
     * @param string $state The state to set for the entity. Must be either "create" or "update".
     * @return self The current instance with the updated state.
     * @throws EntityValidationWithErrorsException If the provided state is invalid.
     */
    public function setEntityState(string $state): self
    {
        if (!in_array($state, [self::STATE_CREATE, self::STATE_UPDATE])) {
            throw new EntityValidationWithErrorsException(['entity_state' => 'Invalid entity state. Must be either "create" or "update".']);
        }
        $this->entityState = $state;
        return $this;
    }
    
    /**
     * Gets the current entity state.
     *
     * @return string The current entity state.
     */
    protected function getEntityState(): string
    {
        return $this->entityState;
    }
}
