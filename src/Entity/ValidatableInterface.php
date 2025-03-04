<?php

declare(strict_types=1);

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
     * @return bool True if all properties pass validation, false if any errors are found.
     */
    public function validate(): bool;
    
    /**
     * Adds a validator for a specified property.
     *
     * @param string $property The name of the property for which the validator is being added.
     * @param ValidatorInterface $validator The validator to be associated with the specified property.
     * @return void
     */
    public function addValidator(string $property, ValidatorInterface $validator): void;
    
    /**
     * Retrieves a list of validation errors.
     *
     * @return array An array containing the validation errors.
     */
    public function getErrors(): array;

}
