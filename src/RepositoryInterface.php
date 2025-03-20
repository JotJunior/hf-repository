<?php

namespace Jot\HfRepository;

use Jot\HfRepository\Entity\EntityInterface;

interface RepositoryInterface
{

    public function find(string $id): ?EntityInterface;

    public function first(array $params): ?EntityInterface;

    public function paginate(array $params, int $page = 1, int $perPage = 10): array;

    public function create(EntityInterface $entity): EntityInterface;

    public function update(EntityInterface $entity): EntityInterface;

    public function delete(string $id): bool;

}
