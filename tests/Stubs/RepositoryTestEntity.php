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

namespace Jot\HfRepository\Tests\Stubs;

use Jot\HfRepository\Entity\EntityInterface;
use Jot\HfRepository\Entity\Traits\HydratableTrait;

/**
 * Test entity implementation for testing Repository class.
 */
class RepositoryTestEntity implements EntityInterface
{
    use HydratableTrait;

    private string $id;

    private string $name;

    private array $errors = [];

    private bool $isValid = true;

    /**
     * Magic method to get entity properties.
     */
    public function __get(string $name): mixed
    {
        return $this->{$name} ?? null;
    }

    /**
     * Get the entity ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set the entity ID.
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get the entity name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the entity name.
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Convert entity to array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id ?? '',
            'name' => $this->name ?? '',
        ];
    }

    /**
     * Validate the entity.
     */
    public function validate(): bool
    {
        return $this->isValid;
    }

    /**
     * Get validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Set validation status for testing.
     */
    public function setValidationStatus(bool $isValid, array $errors = []): self
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
        return $this;
    }
}
