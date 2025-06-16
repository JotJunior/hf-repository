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

interface ReadableServiceInterface
{
    public function paginate(array $query, array $filters = []): array;

    public function autocomplete(string $keyword): array;

    public function search(string $keyword): array;

    public function getData(string $id): array;
}
