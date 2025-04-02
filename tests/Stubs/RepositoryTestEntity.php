<?php

declare(strict_types=1);

namespace Jot\HfRepository\Tests\Stubs;

use Jot\HfRepository\Entity\EntityInterface;
use Jot\HfRepository\Entity\Traits\HydratableTrait;

/**
 * Test entity implementation for testing Repository class.
 */
class RepositoryTestEntity implements EntityInterface
{
    use HydratableTrait;
    
    /**
     * @var string
     */
    private string $id;
    
    /**
     * @var string
     */
    private string $name;
    
    /**
     * @var array
     */
    private array $errors = [];
    
    /**
     * @var bool
     */
    private bool $isValid = true;
    
    /**
     * Get the entity ID
     * 
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * Set the entity ID
     * 
     * @param string $id
     * @return self
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }
    
    /**
     * Get the entity name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Set the entity name
     * 
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * Convert entity to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id ?? '',
            'name' => $this->name ?? ''
        ];
    }
    
    /**
     * Validate the entity
     * 
     * @return bool
     */
    public function validate(): bool
    {
        return $this->isValid;
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Set validation status for testing
     * 
     * @param bool $isValid
     * @param array $errors
     * @return self
     */
    public function setValidationStatus(bool $isValid, array $errors = []): self
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
        return $this;
    }
    
    /**
     * Magic method to get entity properties
     * 
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->$name ?? null;
    }
}
