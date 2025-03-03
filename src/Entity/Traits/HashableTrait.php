<?php

declare(strict_types=1);

namespace Jot\HfRepository\Entity\Traits;

/**
 * Trait that provides hashing functionality.
 */
trait HashableTrait
{
    /**
     * Creates a hashed version of the specified property using a salt and updates it in the current object.
     *
     * @param string $property The name of the property to hash.
     * @param string $salt The salt to use for hashing.
     * @param string $encryptionKey The encryption key to use for hashing.
     * @return self The current instance with the hashed property.
     */
    public function createHash(string $property, string $salt, string $encryptionKey): self
    {
        if (property_exists($this, $property)) {
            $this->$property = hash_hmac('sha256', $this->$property . $salt, $encryptionKey);
        }
        return $this;
    }
}
