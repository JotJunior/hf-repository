<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
 * @license  MIT
 */

namespace Jot\HfRepository\Entity\Traits;

/**
 * Trait that provides hashing functionality.
 */
trait HashableTrait
{
    /**
     * Creates a hashed version of the specified property using a salt and updates it in the current object.
     *
     * @param string $property the name of the property to hash
     * @param string $salt the salt to use for hashing
     * @param string $encryptionKey the encryption key to use for hashing
     * @return self the current instance with the hashed property
     */
    public function createHash(string $property, string $salt, string $encryptionKey): self
    {
        if (property_exists($this, $property)) {
            $this->{$property} = hash_hmac('sha256', $this->{$property} . $salt, $encryptionKey);
        }
        return $this;
    }
}
