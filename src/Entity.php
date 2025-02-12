<?php

declare(strict_types=1);

namespace Jot\HfRepository;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Stringable\Str;
use Hyperf\Swagger\Annotation as SA;
use Jot\HfRepository\Event\AfterEntityHydration;
use Jot\HfRepository\Exception\EntityValidationWithErrorsException;
use Jot\HfRepository\Exception\InvalidEntityException;
use Jot\HfValidator\ValidatorChain;
use Jot\HfValidator\ValidatorInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function Hyperf\Support\make;

abstract class Entity implements EntityInterface
{

    const STATE_CREATE = 'create';
    const STATE_UPDATE = 'update';
    protected EventDispatcherInterface $eventDispatcher;
    protected ?string $id = null;
    protected array $validators = [];
    protected array $errors = [];
    protected array $hiddenProperties = [
        '@timestamp',
        'deleted',
        'entity_state',
        'errors',
        'event_dispatcher',
        'hidden_properties',
        'logger',
        'validators',
    ];
    private string $entityState = self::STATE_CREATE;

    public function __construct(array $data, ContainerInterface $container, protected StdoutLoggerInterface $logger)
    {
        $this->hydrate($data);
        if ($container->has(EventDispatcherInterface::class)) {
            $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
            $this->eventDispatcher->dispatch(new AfterEntityHydration($this));
        }
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

            if (!$this->propertyExistsInEntity($property)) {
                continue;
            }

            try {
                $relatedClass = $this->getRelatedClassFromAttributes($property);
                if (!empty($relatedClass) && class_exists($relatedClass)) {
                    $this->$property = make($relatedClass, ['data' => $value]);
                } else {
                    $this->$property = $value;
                }
            } catch (\Throwable $throwable) {
                $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
            }
        }

        return $this;
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
            $properties = array_merge(
                $properties,
                $trait->getProperties()
            );
        }

        return $properties;
    }

    private function getRelatedClassFromAttributes(string $property): ?string
    {
        $reflection = new \ReflectionProperty($this, $property);
        $attributes = $reflection->getAttributes(SA\Property::class);

        foreach ($attributes as $attribute) {
            $annotation = $attribute->newInstance();

            if (isset($annotation->x['php_type']) && is_string($annotation->x['php_type'])) {
                return $annotation->x['php_type'];
            }
        }

        return null;
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
            if (in_array(Str::snake($propertyName), $this->hiddenProperties)) {
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
            $value instanceof \DateTime, $value instanceof \DateTimeImmutable => $value->format(DATE_ATOM),
            default => $value,
        };

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
     * Validates the properties of the current entity using defined validators.
     *
     * @return bool True if all properties pass validation, false if any errors are found.
     */
    public function validate(): bool
    {
        foreach (ValidatorChain::list(get_class($this)) as $property => $validators) {
            foreach ($validators as $validator) {
                $isValid = $this->entityState === self::STATE_UPDATE
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

    /**
     * Creates a hashed version of the specified property using a salt and updates it in the current object.
     *
     * @param string $property The name of the property to hash.
     * @param string $salt The salt to use for hashing.
     * @return self The current instance with the hashed property.
     */
    public function createHash(string $property, string $salt, string $encryptionKey): self
    {
        if (property_exists($this, $property)) {
            $this->$property = hash_hmac('sha256', $this->$property . $salt, $encryptionKey);
        }
        return $this;
    }

    /**
     * Sets the current state of the entity.
     *
     * @param string $state The state to set for the entity. Must be either "create" or "update".
     * @return self The current instance with the updated state.
     * @throws EntityValidationWithErrorsException If the provided state is invalid.
     */
    public function setEntityState(string $state): self
    {
        if (!in_array($state, [self::STATE_CREATE, self::STATE_UPDATE])) {
            throw new EntityValidationWithErrorsException(['entity_state' => 'Invalid entity state. Must be either "create" or "update".']);
        }
        $this->entityState = $state;
        return $this;
    }

    /**
     * Retrieves the value of an inaccessible or non-public property.
     *
     * @param string $name The name of the property to retrieve.
     * @return mixed The value of the requested property.
     * @throws InvalidEntityException If the property does not exist in the object.
     */
    public function __get($name)
    {
        if (!property_exists($this, $name)) {
            throw new InvalidEntityException();
        }
        return $this->$name;
    }

}