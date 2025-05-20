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

namespace Jot\HfRepository\Service\Contract;

interface WritableServiceInterface
{
    public function create(array $data): array;

    public function update(string $id, array $data): array;

    public function delete(string $id): array;
}
