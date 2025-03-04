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
     * @throws \ReflectionException
     */
    public function create(string $entityClass, array $data)
    {
        // Check if the entity constructor expects an array
        $reflection = new \ReflectionClass($entityClass);
        $constructor = $reflection->getConstructor();
        
        if ($constructor) {
            $params = $constructor->getParameters();
            if (count($params) === 1 && $params[0]->getType() && $params[0]->getType()->getName() === 'array') {
                // If the constructor expects a single array parameter, pass the data as a single parameter
                return make($entityClass, [$data]);
            }
        }
        
        // Otherwise, pass the data as individual parameters
        return make($entityClass, $data);
    }
    
    /**
     * Makes the factory invocable, allowing it to be used as a function.
     * 
     * @param string $entityClass The fully qualified class name of the entity to create
     * @param array $data The data to initialize the entity with
     * @return mixed The created entity instance
     * @throws \ReflectionException
     */
    public function __invoke(string $entityClass, array $data)
    {
        return $this->create($entityClass, $data);
    }
}
