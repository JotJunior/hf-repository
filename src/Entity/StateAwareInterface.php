<?php

declare(strict_types=1);

namespace Jot\HfRepository\Entity;

/**
 * Interface for entities that can track their state (create/update).
 */
interface StateAwareInterface
{
    /**
     * Sets the current state of the entity.
     *
     * @param string $state The state to set for the entity.
     * @return self The current instance with the updated state.
     */
    public function setEntityState(string $state): self;
}
