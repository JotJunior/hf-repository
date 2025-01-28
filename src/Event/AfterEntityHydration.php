<?php

namespace Jot\HfRepository\Event;

use Jot\HfRepository\EntityInterface;

class AfterEntityHydration
{
    public function __construct(public EntityInterface $entity)
    {

    }

    public function getEntity(): EntityInterface
    {
        return $this->entity;
    }
}
