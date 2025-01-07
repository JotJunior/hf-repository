<?php

namespace Jot\HfRepository;

interface RepositoryInterface
{

    public function find(string $id): EntityInterface;

    public function first(array $query): EntityInterface;

    public function paginate(array $query, int $page = 1, int $perPage = 10): array;

    public function all(array $query): array;

    public function create(EntityInterface $entity): EntityInterface;

    public function update(EntityInterface $entity): EntityInterface;

    public function delete(string $id): bool;

}