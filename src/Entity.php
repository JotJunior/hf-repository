<?php

declare(strict_types=1);

namespace Jot\HfRepository;

use Hyperf\Stringable\Str;
use Jot\HfRepository\Event\AfterEntityHydration;
use Jot\HfValidator\ValidatorInterface;
use Hyperf\Swagger\Annotation as SA;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function Hyperf\Support\make;

abstract class Entity implements EntityInterface
{

    protected EventDispatcherInterface $eventDispatcher;
    protected ?string $id = null;
    protected array $validators = [];
    protected array $errors = [];
    protected array $hiddenProperties = ['@timestamp', 'deleted', 'eventDispatcher', 'hiddenProperties', 'validators', 'errors'];

    public function __construct(array $data, ContainerInterface $container)
    {
        $this->hydrate($data);
        if ($container->has(EventDispatcherInterface::class)) {
            $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
            $this->eventDispatcher->dispatch(new AfterEntityHydration($this));
        }
    }

    /**
     * Retrieves the ID of the current entity.
     *
     * @return string|null The ID of the entity, or null if not set.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Populates the current entity's properties with the provided key-value pairs.
     *
     * @param array $data An associative array where keys represent property names (in snake_case)
     *                    and values are the corresponding values to set.
     * @return self Returns the current instance with the updated properties.
     * @throws \ReflectionException
     */
    public function hydrate(array $data): self
    {
        foreach ($data as $key => $value) {
            $property = Str::camel($key);
            if ($this->propertyExistsInEntity($property)) {
                $reflection = new \ReflectionProperty($this, $property);
                $attributes = $reflection->getAttributes(SA\Property::class);
                $relatedClass = null;
                foreach ($attributes as $attribute) {
                    $annotation = $attribute->newInstance();
                    if (isset($annotation->x) && is_array($annotation->x)) {
                        $relatedClass = $annotation->x['php_type'];
                    }
                }
                if (!empty($relatedClass) && class_exists($relatedClass)) {
                    $this->$property = make($relatedClass, ['data' => $value]);
                } else {
                    $this->$property = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Converts the properties of the current object to an associative array representation.
     *
     * @return array An associative array where keys are property names (in snake_case)
     *               and values are their corresponding values. If a property value is an object
     *               with a toArray method, its array representation is recursively included.
     *               If a property value is an array, its elements are processed similarly.
     */
    public function toArray(): array
    {
        $array = [];
        $reflection = new \ReflectionClass($this);

        foreach ($this->getAllProperties($reflection) as $property) {
            $propertyName = $property->getName();
            if (in_array($propertyName, $this->hiddenProperties)) {
                continue;
            }
            $property->setAccessible(true);

            try {
                $value = $property->getValue($this);
            } catch (\Throwable $e) {
                continue;
            }

            $array[Str::snake($propertyName)] = $this->extractVariables($value);
        }

        return array_filter($array);
    }

    /**
     * Extracts variables by transforming the input value based on its type or characteristics.
     *
     * @param mixed $value The input value to be transformed. This can be an object, DateTime instance, or any other type.
     * @return mixed The transformed value. Returns the array representation if the object has a toArray method,
     *               a formatted date string if the value is a DateTime instance, or the value itself otherwise.
     */
    private function extractVariables(mixed $value): mixed
    {
        return match (true) {
            is_object($value) && method_exists($value, 'toArray') => $value->toArray(),
            $value instanceof \DateTime => $value->format(DATE_ATOM),
            default => $value,
        };

    }

    /**
     * Checks if a given property exists in the current entity.
     *
     * @param string $property The name of the property to check for.
     * @return bool True if the property exists, false otherwise.
     */
    private function propertyExistsInEntity(string $property): bool
    {
        $reflection = new \ReflectionClass($this);
        foreach ($this->getAllProperties($reflection) as $prop) {
            if ($prop->getName() === $property) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves all properties of a class, including those from its traits.
     *
     * @param \ReflectionClass $reflection The reflection instance of the class.
     * @return array An array of properties belonging to the class and its traits.
     */
    private function getAllProperties(\ReflectionClass $reflection): array
    {
        $properties = $reflection->getProperties();

        foreach ($reflection->getTraits() as $trait) {
            $properties = array_merge($properties, $trait->getProperties());
        }

        return $properties;
    }

    /**
     * Creates and returns a copy of the current object.
     *
     * @return self A new instance that is a clone of the current object.
     */
    public function clone(): self
    {
        return clone $this;
    }

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
     * Validates the current object's properties using predefined validators.
     *
     * Iterates through the validators assigned to the properties of the object,
     * applying each validator to its corresponding property. If any validation fails,
     * the errors are stored, and the validation result is marked as unsuccessful.
     *
     * @return bool True if all validations pass, false if any property validation fails.
     */
    public function validate(): bool
    {
        foreach ($this->validators as $property => $validator) {
            if (!$validator->validate($this->$property)) {
                $this->errors[$property] = $validator->getErrors();
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
     * Hides the specified property or properties by adding them to a list of hidden properties.
     *
     * @param string|array $property The property name or an array of property names to hide.
     * @return self The current instance for method chaining.
     */
    public function hide(string|array $property): self
    {
        if (is_array($property)) {
            $this->hiddenProperties = [
                ...$property,
                ...$this->hiddenProperties,
            ];
        } else if (is_string($property))
            $this->hiddenProperties[] = $property;

        return $this;
    }

}