<?php

declare(strict_types=1);

namespace Jot\HfRepository\Entity\Traits;

use Hyperf\Stringable\Str;
use Hyperf\Swagger\Annotation as SA;
use Jot\HfRepository\Entity;
use Jot\HfRepository\Entity\EntityFactoryInterface;
use Jot\HfRepository\Tests\Entity\Traits\HydratableTraitScalarTestClass;
use Jot\HfRepository\Tests\Entity\Traits\HydratableTraitTestClass;

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

            if (!$this->propertyExistsInEntity($property)) {
                continue;
            }

            $relatedClass = $this->getRelatedClassFromAttributes($property);
            if (!empty($relatedClass) && class_exists($relatedClass)) {
                $this->hydrateRelatedProperty($property, $relatedClass, $value);
            } else {
                $this->$property = $value;
            }
        }

        return $this;
    }

    /**
     * Hydrates a related property of the current object with the specified value,
     * based on the provided related class type.
     * @param string $property The name of the property to be hydrated.
     * @param string $relatedClass The fully-qualified class name of the related entity.
     * @param mixed $value The value used to hydrate the related property.
     * @return void
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

        $this->$property = new $relatedClass();
        if (method_exists($this->$property, 'hydrate') && is_scalar($value)) {
            $this->$property->hydrate(['id' => $value]);
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
     *
     * @param string $property The property name to check.
     * @return string|null The related class name or null if not found.
     * @throws \ReflectionException
     */
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
}
