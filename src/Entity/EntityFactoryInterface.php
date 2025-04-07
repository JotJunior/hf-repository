<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
 * @license  MIT
 */

namespace Jot\HfRepository\Entity;

/**
 * Interface for entity factory functionality.
 *
 * This interface defines the contract for classes that create entity instances.
 * It follows the Interface Segregation Principle by providing a focused interface
 * for entity creation.
 */
interface EntityFactoryInterface
{
    /**
     * Creates an entity instance of the specified class with the given data.
     *
     * @param string $entityClass The fully qualified class name of the entity to create
     * @param array $data The data to initialize the entity with
     * @return mixed The created entity instance
     */
    public function create(string $entityClass, array $data);
}
