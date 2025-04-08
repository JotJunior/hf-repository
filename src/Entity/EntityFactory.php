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

namespace Jot\HfRepository\Entity;

use ReflectionClass;
use ReflectionException;

use function Hyperf\Support\make;

/**
 * Default implementation of EntityFactoryInterface.
 * This class is responsible for creating entity instances.
 * It follows the Single Responsibility Principle by focusing solely on entity creation.
 */
class EntityFactory implements EntityFactoryInterface
{
    /**
     * Creates an entity instance of the specified class with the given data.
     * @param string $entityClass The fully qualified class name of the entity to create
     * @param array $data The data to initialize the entity with
     * @return mixed The created entity instance
     * @throws ReflectionException
     */
    public function create(string $entityClass, array $data)
    {
        // Check if the entity constructor expects an array
        $reflection = new ReflectionClass($entityClass);
        $constructor = $reflection->getConstructor();

        if ($constructor) {
            $params = $constructor->getParameters();
            if (count($params) === 1 && $params[0]->getType() && $params[0]->getType()->getName() === 'array') {
                // Constructor expects a single array parameter
                return make($entityClass, ['data' => $data]);
            }
            // Constructor expects individual parameters
            // Map data array keys to constructor parameter names
            $constructorArgs = [];
            foreach ($params as $param) {
                $paramName = $param->getName();
                if (array_key_exists($paramName, $data)) {
                    $constructorArgs[$paramName] = $data[$paramName];
                }
            }
            return make($entityClass, $constructorArgs);
        }

        // Fallback to creating without constructor arguments
        $instance = $reflection->newInstance();

        // Set public properties directly
        foreach ($data as $key => $value) {
            if ($reflection->hasProperty($key)) {
                $property = $reflection->getProperty($key);
                if ($property->isPublic()) {
                    $instance->{$key} = $value;
                }
            }
        }

        return $instance;
    }
}
