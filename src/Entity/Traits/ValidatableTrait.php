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

namespace Jot\HfRepository\Entity\Traits;

use Jot\HfValidator\ValidatorChain;
use Jot\HfValidator\ValidatorInterface;

/**
 * Trait that provides validation functionality.
 */
trait ValidatableTrait
{
    /**
     * List of validators for entity properties.
     */
    protected array $validators = [];

    /**
     * List of validation errors.
     */
    protected array $errors = [];

    /**
     * Adds a validator for a specified property.
     *
     * @param string $property the name of the property for which the validator is being added
     * @param ValidatorInterface $validator the validator to be associated with the specified property
     */
    public function addValidator(string $property, ValidatorInterface $validator): void
    {
        $this->validators[$property][] = $validator;
    }

    /**
     * Validates the properties of the current entity using defined validators.
     *
     * @return bool true if all properties pass validation, false if any errors are found
     */
    public function validate(): bool
    {
        $this->errors = [];
        $this->validators = array_merge($this->validators, ValidatorChain::list(get_class($this)));

        $validated = [];

        foreach ($this->validators as $property => $validators) {
            if (! empty($validated[$property])) {
                continue;
            }
            foreach ($validators as $validator) {
                $isValid = $validator
                    ->setContext($this->entityState)
                    ->setProperty($property)
                    ->setIdentifier($this->id)
                    ->validate($this->{$property});
                if (! $isValid) {
                    $this->errors[$property] = $validator->consumeErrors($property);
                }
            }
            $validated[$property] = true;
        }

        return empty($this->errors);
    }

    /**
     * Retrieves a list of errors.
     *
     * @return array an array containing the errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
