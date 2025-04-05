<?php

declare(strict_types=1);
/**
 * This file is part of hf-repository
 *
 * @link     https://github.com/JotJunior/hf-repository
 * @contact  hf-repository@jot.com.br
 * @license  MIT
 */

namespace Jot\HfRepository\Entity;

/**
 * Interface for entities that can hash their properties.
 */
interface HashableInterface
{
    /**
     * Creates a hashed version of the specified property using a salt and updates it in the current object.
     *
     * @param string $property the name of the property to hash
     * @param string $salt the salt to use for hashing
     * @param string $encryptionKey the encryption key to use for hashing
     * @return self the current instance with the hashed property
     */
    public function createHash(string $property, string $salt, string $encryptionKey): self;
}
