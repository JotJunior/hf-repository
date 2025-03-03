<?php

declare(strict_types=1);

namespace Jot\HfRepository\Entity\Traits;

use Jot\HfValidator\ValidatorChain;
use Jot\HfValidator\ValidatorInterface;

/**
 * Trait that provides validation functionality.
 */
trait ValidatableTrait
{
    /**
     * List of validators for entity properties
     */
    protected array $validators = [];
    
    /**
     * List of validation errors
     */
    protected array $errors = [];
    
    /**
     * Adds a validator for a specified property.
     *
     * @param string $property The name of the property for which the validator is being added.
     * @param ValidatorInterface $validator The validator to be associated with the specified property.
     * @return void
     */
    public function addValidator(string $property, ValidatorInterface $validator): void
    {
        $this->validators[$property] = $validator;
    }
    
    /**
     * Validates the properties of the current entity using defined validators.
     *
     * @return bool True if all properties pass validation, false if any errors are found.
     */
    public function validate(): bool
    {
        foreach (ValidatorChain::list(get_class($this)) as $property => $validators) {
            foreach ($validators as $validator) {
                $isValid = $this->getEntityState() === self::STATE_UPDATE
                    ? $validator->onUpdate()->validate($this->$property)
                    : $validator->validate($this->$property);
                    
                if ($validator && !$isValid) {
                    $this->errors[$property] = $validator->consumeErrors();
                }
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Retrieves a list of errors.
     *
     * @return array An array containing the errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Gets the current entity state.
     * This method should be implemented by the class using this trait.
     *
     * @return string The current entity state.
     */
    abstract protected function getEntityState(): string;
}
