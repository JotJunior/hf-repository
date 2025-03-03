<?php

declare(strict_types=1);

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
     * @return string|null The ID of the entity, or null if not set.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Sets the ID for the current entity.
     *
     * @param string|null $id The ID to set, or null to unset the ID.
     * @return EntityIdentifierTrait|Entity Returns the current instance for method chaining.
     */
    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }


}
