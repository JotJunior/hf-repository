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

/**
 * Core interface for all entities in the system.
 */
interface EntityInterface extends EntityIdentifierInterface, HashableInterface, PropertyVisibilityInterface, StateAwareInterface, ValidatableInterface
{
    public function __get(string $name): mixed;
}
