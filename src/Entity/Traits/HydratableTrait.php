<?php

declare(strict_types=1);

namespace Jot\HfRepository\Entity\Traits;

use Hyperf\Stringable\Str;
use Hyperf\Swagger\Annotation as SA;
use Jot\HfRepository\Entity;
use Jot\HfRepository\Entity\EntityFactoryInterface;
use Jot\HfRepository\Tests\Entity\Traits\HydratableTraitScalarTestClass;
use Jot\HfRepository\Tests\Entity\Traits\HydratableTraitTestClass;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Trait that provides hydration functionality.
 */
trait HydratableTrait
{

    /**
     * Populates the current object with data from the provided array.
     * @param array $data An associative array where keys correspond to property names and values are the values to be assigned.
     * @return HydratableTrait|Entity|HydratableTraitScalarTestClass|HydratableTraitTestClass Returns the instance of the current object after hydration.
     * @throws \ReflectionException
     */
    public function hydrate(array $data): self
    {
        foreach ($data as $key => $value) {
            $property = Str::camel($key);

            if($key === 'id' && is_bool($value)) {
                $value = '-';
            }

            if (!$this->propertyExistsInEntity($property)) {
                continue;
            }

            list($relatedClass, $params) = $this->getRelatedClassFromAttributes($property);

            if (!empty($relatedClass) && class_exists($relatedClass)) {
                $this->hydrateRelatedProperty($property, $relatedClass, $value, $params);
            } else {
                $this->$property = $value;
            }
        }

        return $this;
    }

    /**
     * Checks if a given property exists in the current entity.
     *
     * @param string $property The name of the property to check for.
     * @return bool True if the property exists, false otherwise.
     * @throws \ReflectionException
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

    /**
     * Gets the related class from property attributes.
     * @param string $property The property name to check.
     * @return array|null The related class name and params or null if not found.
     * @throws \ReflectionException
     */
    private function getRelatedClassFromAttributes(string $property): ?array
    {
        $reflection = new \ReflectionProperty($this, $property);
        $attributes = $reflection->getAttributes(SA\Property::class);

        foreach ($attributes as $attribute) {
            $annotation = $attribute->newInstance();

            if (isset($annotation->x['php_type']) && is_string($annotation->x['php_type'])) {
                return [$annotation->x['php_type'], $annotation->x['params'] ?? []];
            }
        }

        return null;
    }

    /**
     * Hydrates a related property of the current object with the specified value,
     * based on the provided related class type.
     * @param string $property The name of the property to be hydrated.
     * @param string $relatedClass The fully-qualified class name of the related entity.
     * @param mixed $value The value used to hydrate the related property.
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function hydrateRelatedProperty(string $property, string $relatedClass, mixed $value): void
    {
        if ($this->isDateTimeClass($relatedClass) && $value) {
            $this->$property = new $relatedClass($value);
            return;
        }

        $entityFactory = $this->getEntityFactory();
        if ($entityFactory instanceof EntityFactoryInterface && is_array($value)) {
            $this->$property = $entityFactory->create($relatedClass, $value);
            return;
        }

    }

    /**
     * Checks if a given class name contains the substring 'DateTime'.
     * @param string $className The name of the class to be checked.
     * @return bool Returns true if the class name contains 'DateTime', otherwise false.
     */
    private function isDateTimeClass(string $className): bool
    {
        return str_contains($className, 'DateTime');
    }

    /**
     * Converts the properties of the current object to an associative array representation.
     *
     * @return array An associative array where keys are property names (in snake_case)
     *               and values are their corresponding values. If a property value is an object
     *               with a toArray method, its array representation is recursively included.
     *               If a property value is an array, its elements are processed similarly.
     * @throws \ReflectionException
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
            $params = $this->getRelatedClassFromAttributes($propertyName)[1] ?? null;

            try {
                $value = $property->getValue($this);
            } catch (\Throwable $e) {
                continue;
            }

            $array[Str::snake($propertyName)] = $this->extractVariables($value, $params);
        }

        return array_filter($array);
    }


    /**
     * Extracts variables from the provided value based on its type or content.
     * For objects with a `toArray` method, it returns the result of the `toArray` call.
     * For DateTime or DateTimeImmutable instances, it formats the value using the specified format.
     * Otherwise, it returns the value unchanged.
     * @param mixed $value The value to extract or format, which may be an object, DateTime, or any other type.
     * @param array|null $params The params to use for DateTime or DateTimeImmutable instances. Defaults to DATE_ATOM.
     * @return mixed Returns the extracted or formatted value based on the input type.
     */
    private function extractVariables(mixed $value, ?array $params = null): mixed
    {
        return match (true) {
            is_object($value) && method_exists($value, 'toArray') => $value->toArray(),
            $value instanceof \DateTime, $value instanceof \DateTimeImmutable => $value->format($params['format'] ?? DATE_ATOM),
            default => $value,
        };

    }
}
