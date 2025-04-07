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

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Hyperf\Stringable\Str;
use Hyperf\Swagger\Annotation as SA;
use Jot\HfRepository\Entity;
use Jot\HfRepository\Entity\EntityFactoryInterface;
use Jot\HfRepository\Tests\Entity\Traits\HydratableTraitScalarTestClass;
use Jot\HfRepository\Tests\Entity\Traits\HydratableTraitTestClass;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Throwable;

/**
 * Trait that provides hydration functionality.
 */
trait HydratableTrait
{
    /**
     * Populates the current object with data from the provided array.
     * @param array $data an associative array where keys correspond to property names and values are the values to be assigned
     * @return Entity|HydratableTrait|HydratableTraitScalarTestClass|HydratableTraitTestClass returns the instance of the current object after hydration
     * @throws ReflectionException
     */
    public function hydrate(array $data): self
    {
        foreach ($data as $key => $value) {
            $property = Str::camel($key);

            if ($key === 'id' && is_bool($value)) {
                $value = '-';
            }

            if (! $this->propertyExistsInEntity($property)) {
                continue;
            }

            [$relatedClass, $params] = $this->getRelatedClassFromAttributes($property);

            if (! empty($relatedClass) && class_exists($relatedClass)) {
                $this->hydrateRelatedProperty($property, $relatedClass, $value, $params);
            } else {
                $this->{$property} = $value;
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
     * @throws ReflectionException
     */
    public function toArray(): array
    {
        $array = [];
        $reflection = new ReflectionClass($this);

        foreach ($this->getAllProperties($reflection) as $property) {
            $propertyName = $property->getName();
            if (in_array(Str::snake($propertyName), $this->hiddenProperties)) {
                continue;
            }
            $property->setAccessible(true);
            $params = $this->getRelatedClassFromAttributes($propertyName)[1] ?? null;

            try {
                $value = $property->getValue($this);
            } catch (Throwable $e) {
                continue;
            }

            $array[Str::snake($propertyName)] = $this->extractVariables($value, $params);
        }

        return array_filter($array);
    }

    /**
     * Checks if a given property exists in the current entity.
     *
     * @param string $property the name of the property to check for
     * @return bool true if the property exists, false otherwise
     * @throws ReflectionException
     */
    private function propertyExistsInEntity(string $property): bool
    {
        $reflection = new ReflectionClass($this);
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
     * @param ReflectionClass $reflection the reflection instance of the class
     * @return array an array of properties belonging to the class and its traits
     */
    private function getAllProperties(ReflectionClass $reflection): array
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
     * @param string $property the property name to check
     * @return null|array the related class name and params or null if not found
     * @throws ReflectionException
     */
    private function getRelatedClassFromAttributes(string $property): ?array
    {
        $reflection = new ReflectionProperty($this, $property);
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
     * @param string $property the name of the property to be hydrated
     * @param string $relatedClass the fully-qualified class name of the related entity
     * @param mixed $value the value used to hydrate the related property
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function hydrateRelatedProperty(string $property, string $relatedClass, mixed $value): void
    {
        if ($this->isDateTimeClass($relatedClass) && is_string($value)) {
            $this->{$property} = new $relatedClass($value);
            return;
        }
        if ($this->isDateTimeClass($relatedClass) && $value instanceof DateTimeInterface) {
            $this->{$property} = $value;
            return;
        }

        $entityFactory = $this->getEntityFactory();
        if ($entityFactory instanceof EntityFactoryInterface && is_array($value)) {
            $this->{$property} = $entityFactory->create($relatedClass, $value);
            return;
        }

        // Tratar valores escalares ou arrays quando não há EntityFactory
        if (is_scalar($value) || is_array($value)) {
            // Verificar se a classe relacionada tem um método hydrate
            if (method_exists($relatedClass, 'hydrate')) {
                $instance = new $relatedClass();
                if (is_scalar($value)) {
                    // Para valores escalares, assumimos que é o ID
                    $instance->hydrate(['id' => $value]);
                } else {
                    // Para arrays, passamos diretamente para hydrate
                    $instance->hydrate($value);
                }
                $this->{$property} = $instance;
            } elseif (is_array($value)) {
                // Se não tem método hydrate mas é um array, tentamos criar a instância com o construtor
                $this->{$property} = new $relatedClass($value);
            } elseif (is_scalar($value)) {
                // Para valores escalares em classes sem hydrate, criamos a instância e tentamos definir o ID
                $instance = new $relatedClass();
                if (property_exists($instance, 'id')) {
                    $instance->id = $value;
                }
                $this->{$property} = $instance;
            }
        }
    }

    /**
     * Checks if a given class name contains the substring 'DateTime'.
     * @param string $className the name of the class to be checked
     * @return bool returns true if the class name contains 'DateTime', otherwise false
     */
    private function isDateTimeClass(string $className): bool
    {
        return str_contains($className, 'DateTime');
    }

    /**
     * Extracts variables from the provided value based on its type or content.
     * For objects with a `toArray` method, it returns the result of the `toArray` call.
     * For DateTime or DateTimeImmutable instances, it formats the value using the specified format.
     * Otherwise, it returns the value unchanged.
     * @param mixed $value the value to extract or format, which may be an object, DateTime, or any other type
     * @param null|array $params The params to use for DateTime or DateTimeImmutable instances. Defaults to DATE_ATOM.
     * @return mixed returns the extracted or formatted value based on the input type
     */
    private function extractVariables(mixed $value, ?array $params = null): mixed
    {
        return match (true) {
            is_object($value) && method_exists($value, 'toArray') => $value->toArray(),
            $value instanceof DateTime, $value instanceof DateTimeImmutable => $value->format($params['format'] ?? DATE_ATOM),
            default => $value,
        };
    }
}
