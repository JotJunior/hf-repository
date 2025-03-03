<?php

declare(strict_types=1);

namespace Jot\HfRepository\Entity;

use function Hyperf\Support\make;

/**
 * Default implementation of EntityFactoryInterface.
 * 
 * This class is responsible for creating entity instances.
 * It follows the Single Responsibility Principle by focusing solely on entity creation.
 */
class EntityFactory implements EntityFactoryInterface
{
    /**
     * Creates an entity instance of the specified class with the given data.
     *
     * @param string $entityClass The fully qualified class name of the entity to create
     * @param array $data The data to initialize the entity with
     * @return mixed The created entity instance
     */
    public function create(string $entityClass, array $data)
    {
        return make($entityClass, $data);
    }
}
