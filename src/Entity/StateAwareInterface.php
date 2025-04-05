<?php

declare(strict_types=1);
/**
 * This file is part of hf-repository
 *
 * @link     https://github.com/JotJunior/hf-repository
 * @contact  hf-repository@jot.com.br
 * @license  MIT
 */

namespace Jot\HfRepository\Entity;

/**
 * Interface for entities that can track their state (create/update).
 */
interface StateAwareInterface
{
    /**
     * Sets the current state of the entity.
     *
     * @param string $state the state to set for the entity
     * @return self the current instance with the updated state
     */
    public function setEntityState(string $state): self;
}
