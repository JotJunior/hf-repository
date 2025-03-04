<?php

declare(strict_types=1);

namespace Jot\HfRepository;

use Hyperf\Contract\Arrayable;
use Jot\HfRepository\Entity\EntityIdentifierInterface;
use Jot\HfRepository\Entity\EntityInterface;
use Jot\HfRepository\Entity\HashableInterface;
use Jot\HfRepository\Entity\PropertyVisibilityInterface;
use Jot\HfRepository\Entity\StateAwareInterface;
use Jot\HfRepository\Entity\Traits\EntityIdentifierTrait;
use Jot\HfRepository\Entity\Traits\EntityStateTrait;
use Jot\HfRepository\Entity\Traits\HashableTrait;
use Jot\HfRepository\Entity\Traits\HydratableTrait;
use Jot\HfRepository\Entity\Traits\PropertyVisibilityTrait;
use Jot\HfRepository\Entity\Traits\ValidatableTrait;
use Jot\HfRepository\Entity\ValidatableInterface;
use Jot\HfRepository\Exception\InvalidEntityException;
use Jot\HfRepository\Entity\EntityFactoryInterface;
use Jot\HfRepository\Entity\EntityFactory;

abstract class Entity implements Arrayable, EntityIdentifierInterface, EntityInterface, HashableInterface, PropertyVisibilityInterface, StateAwareInterface, ValidatableInterface
{

    use EntityIdentifierTrait;
    use EntityStateTrait;
    use HashableTrait;
    use HydratableTrait;
    use PropertyVisibilityTrait;
    use ValidatableTrait;

    protected ?EntityFactoryInterface $entityFactory = null;

    public function __construct(array $data)
    {
        $this->hydrate($data);
    }

    /**
     * Retrieves the value of an inaccessible or non-public property.
     *
     * @param string $name The name of the property to retrieve.
     * @return mixed The value of the requested property.
     * @throws InvalidEntityException If the property does not exist in the object.
     */
    public function __get(string $name): mixed
    {
        if (!property_exists($this, $name)) {
            throw new InvalidEntityException();
        }
        return $this->$name;
    }
    
    /**
     * Gets the entity factory used to create related entities.
     * 
     * @return EntityFactoryInterface|null The entity factory instance or null if not set
     */
    public function getEntityFactory(): ?EntityFactoryInterface
    {
        if ($this->entityFactory === null) {
            // Lazily create a default entity factory if none is set
            $this->entityFactory = new EntityFactory();
        }
        
        return $this->entityFactory;
    }
    
    /**
     * Sets the entity factory to use for creating related entities.
     * 
     * @param EntityFactoryInterface $entityFactory The entity factory instance
     * @return self
     */
    public function setEntityFactory(EntityFactoryInterface $entityFactory): self
    {
        $this->entityFactory = $entityFactory;
        return $this;
    }


}