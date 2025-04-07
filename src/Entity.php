<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
 * @license  MIT
 */

namespace Jot\HfRepository;

use Hyperf\Context\Context;
use Hyperf\Contract\Arrayable;
use Jot\HfRepository\Entity\EntityFactory;
use Jot\HfRepository\Entity\EntityFactoryInterface;
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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Base entity class optimized for Swoole/Hyperf environment.
 * This implementation ensures:
 * - Coroutine safety through Context isolation
 * - Proper dependency injection
 * - Serializable entities for coroutine scheduling
 * - No static property issues.
 */
abstract class Entity implements Arrayable, EntityIdentifierInterface, EntityInterface, HashableInterface, PropertyVisibilityInterface, StateAwareInterface, ValidatableInterface
{
    use EntityIdentifierTrait;
    use EntityStateTrait;
    use HashableTrait;
    use HydratableTrait;
    use PropertyVisibilityTrait;
    use ValidatableTrait;

    /**
     * Context key for entity factory.
     */
    private const CONTEXT_ENTITY_FACTORY = 'entity.factory.';

    /**
     * @Inject
     */
    protected ContainerInterface $container;

    /**
     * Entity factory instance (stored in Context for coroutine safety).
     */
    protected ?string $entityFactoryClass = EntityFactory::class;

    /**
     * Constructor with dependency injection support.
     */
    public function __construct(array $data = [])
    {
        // Hydrate the entity with provided data
        if (! empty($data)) {
            $this->hydrate($data);
        }
    }

    /**
     * Retrieves the value of an inaccessible or non-public property.
     * @param string $name the name of the property to retrieve
     * @return mixed the value of the requested property
     * @throws InvalidEntityException if the property does not exist in the object
     */
    public function __get(string $name): mixed
    {
        if (! property_exists($this, $name)) {
            throw new InvalidEntityException();
        }
        return $this->{$name};
    }

    /**
     * Magic method to handle serialization for coroutine scheduling.
     * Ensures that non-serializable properties are properly handled.
     */
    public function __sleep(): array
    {
        $properties = get_object_vars($this);

        // Remove container and other non-serializable properties
        unset($properties['container']);

        return array_keys($properties);
    }

    /**
     * Magic method to handle unserialization after coroutine scheduling.
     * Restores container and other dependencies.
     */
    public function __wakeup(): void
    {
        // Container will be re-injected by Hyperf's dependency injection
    }

    /**
     * Creates a deep clone of the entity, ensuring all nested objects are cloned.
     * Important for coroutine safety to prevent shared references.
     */
    public function __clone()
    {
        // Deep clone any object properties to prevent shared references
        foreach (get_object_vars($this) as $key => $value) {
            if (is_object($value)) {
                $this->{$key} = clone $value;
            }
        }
    }

    /**
     * Gets the entity factory used to create related entities.
     * Uses Hyperf Context to ensure coroutine safety.
     * @return EntityFactoryInterface The entity factory instance
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getEntityFactory(): EntityFactoryInterface
    {
        $contextKey = self::CONTEXT_ENTITY_FACTORY . spl_object_hash($this);

        // Try to get from context first
        $factory = Context::get($contextKey);

        if ($factory === null) {
            // Create factory through container if possible
            if (isset($this->container) && $this->container->has($this->entityFactoryClass)) {
                $factory = $this->container->get($this->entityFactoryClass);
            } else {
                // Fallback to direct instantiation
                $factoryClass = $this->entityFactoryClass;
                $factory = new $factoryClass();
            }

            // Store in context for this coroutine
            Context::set($contextKey, $factory);
        }

        return $factory;
    }

    /**
     * Sets the entity factory to use for creating related entities.
     * Updates the Hyperf Context to ensure coroutine safety.
     *
     * @param EntityFactoryInterface $entityFactory The entity factory instance
     */
    public function setEntityFactory(EntityFactoryInterface $entityFactory): self
    {
        $contextKey = self::CONTEXT_ENTITY_FACTORY . spl_object_hash($this);
        Context::set($contextKey, $entityFactory);

        return $this;
    }
}
