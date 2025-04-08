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

namespace Jot\HfRepository\Entity\Traits;

use Jot\HfRepository\Entity;

/**
 * Trait that provides entity state functionality.
 */
trait EntityIdentifierTrait
{
    protected ?string $id = null;

    /**
     * Retrieves the ID of the current entity.
     *
     * @return null|string the ID of the entity, or null if not set
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Sets the ID for the current entity.
     *
     * @param null|string $id the ID to set, or null to unset the ID
     * @return Entity|EntityIdentifierTrait returns the current instance for method chaining
     */
    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }
}
