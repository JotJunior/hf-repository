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

namespace Jot\HfRepository;

use Jot\HfRepository\Entity\EntityInterface;

interface RepositoryInterface
{
    public function find(string $id): ?EntityInterface;

    public function first(array $params): ?EntityInterface;

    public function paginate(array $params, int $page = 1, int $perPage = 10, array $filters = []): array;

    public function create(EntityInterface $entity): EntityInterface;

    public function update(EntityInterface $entity): EntityInterface;

    public function delete(string $id): ?array;
}
