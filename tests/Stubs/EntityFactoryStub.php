<?php

declare(strict_types=1);

namespace Jot\HfRepository\Tests\Stubs;

use Jot\HfRepository\Entity\EntityFactoryInterface;
use Jot\HfRepository\Entity\EntityInterface;

/**
 * Stub implementation of EntityFactoryInterface for testing.
 */
class EntityFactoryStub implements EntityFactoryInterface
{
    /**
     * Create an entity instance
     * 
     * @param string $entityClass
     * @param array $data
     * @return EntityInterface
     */
    public function create(string $entityClass, array $data): EntityInterface
    {
        /** @var EntityInterface $entity */
        $entity = new $entityClass();
        
        if (method_exists($entity, 'hydrate')) {
            $entity->hydrate($data);
        } else {
            // Manual hydration if needed
            if (method_exists($entity, 'setId') && isset($data['id'])) {
                $entity->setId($data['id']);
            }
            
            if (method_exists($entity, 'setName') && isset($data['name'])) {
                $entity->setName($data['name']);
            }
        }
        
        return $entity;
    }
}
