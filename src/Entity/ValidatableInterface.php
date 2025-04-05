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

use Jot\HfValidator\ValidatorInterface;

/**
 * Interface for entities that can be validated.
 */
interface ValidatableInterface
{
    /**
     * Validates the entity properties using defined validators.
     *
     * @return bool true if all properties pass validation, false if any errors are found
     */
    public function validate(): bool;

    /**
     * Adds a validator for a specified property.
     *
     * @param string $property the name of the property for which the validator is being added
     * @param ValidatorInterface $validator the validator to be associated with the specified property
     */
    public function addValidator(string $property, ValidatorInterface $validator): void;

    /**
     * Retrieves a list of validation errors.
     *
     * @return array an array containing the validation errors
     */
    public function getErrors(): array;
}
