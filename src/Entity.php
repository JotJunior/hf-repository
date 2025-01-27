<?php

declare(strict_types=1);

namespace Jot\HfRepository;

use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Stringable\Str;
use Jot\HfRepository\Event\AfterHydration;
use Jot\HfValidator\ValidatorInterface;
use Hyperf\Swagger\Annotation as SA;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class Entity implements EntityInterface
{

    protected EventDispatcherInterface $eventDispatcher;
    protected ?string $id = null;
    protected array $validators = [];
    protected array $errors = [];


    public function __construct(array $data, ContainerInterface $container)
    {
        $this->hydrate($data);
        if ($container->has(EventDispatcherInterface::class)) {
            $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
            $this->eventDispatcher->dispatch(new AfterHydration($this));
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
                    $this->$property = new $relatedClass($value);
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

    private function extractVariables(mixed $value): mixed
    {
        return match (true) {
            is_object($value) && method_exists($value, 'toArray') => $value->toArray(),
            $value instanceof \DateTime => $value->format(DATE_ATOM),
            default => $value,
        };

    }

    /**
     * Extracts the related class name from a given doc comment.
     *
     * @param string|null $docComment The doc comment to parse, or null if none is provided.
     * @return string|null The extracted class name if found, or null otherwise.
     */
    private function extractRelatedClass(?string $docComment): ?string
    {
        if ($docComment && preg_match('/@var\s+\\\\?([\w\\\\]+)/', $docComment, $matches)) {
            return explode('|', $matches[1])[0];
        }

        return null;
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

    public function addValidator(string $property, ValidatorInterface $validator): void
    {
        $this->validators[$property] = $validator;
    }

    public function validate(): bool
    {
        foreach ($this->validators as $property => $validator) {
            if (!$validator->validate($this->$property)) {
                $this->errors[$property] = array_merge($validator->getErrors(), $this->errors);
            }
        }
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

}