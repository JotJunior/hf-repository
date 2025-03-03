<?php

declare(strict_types=1);

namespace Jot\HfRepository\Entity;

/**
 * Interface for entities that can hash their properties.
 */
interface HashableInterface
{
    /**
     * Creates a hashed version of the specified property using a salt and updates it in the current object.
     *
     * @param string $property The name of the property to hash.
     * @param string $salt The salt to use for hashing.
     * @param string $encryptionKey The encryption key to use for hashing.
     * @return self The current instance with the hashed property.
     */
    public function createHash(string $property, string $salt, string $encryptionKey): self;
}
