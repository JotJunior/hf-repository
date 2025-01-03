<?php

declare(strict_types=1);

namespace Jot\HfRepository;

abstract class Entity
{

    public function __construct(array $data = [])
    {
        $this->hydrate($data);
    }

    /**
     * Populates the properties of the current object with the provided data.
     *
     * @param array $data An associative array where keys are property names (in snake_case)
     *                    and values are the values to be assigned to the object's properties.
     * @return void
     * @throws \ReflectionException
     */
    public function hydrate(array $data): void
    {
        foreach ($data as $key => $value) {
            $property = $this->snakeToCamelCase($key);
            if ($this->propertyExistsInEntity($property)) {
                $reflection = new \ReflectionProperty($this, $property);
                $docComment = $reflection->getDocComment();
                $relatedClass = $this->extractRelatedClass($docComment);

                if ($relatedClass && class_exists($relatedClass)) {
                    $this->$property = is_array($value)
                        ? new $relatedClass($value)
                        : $value;
                } else {
                    $this->$property = $value;
                }
            }
        }
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
            $value = $property->getValue($this);

            if (is_object($value) && method_exists($value, 'toArray')) {
                $array[$this->camelToSnakeCase($propertyName)] = $value->toArray();
            } elseif (is_array($value)) {
                $array[$this->camelToSnakeCase($propertyName)] = array_map(
                    function ($item) {
                        return is_object($item) && method_exists($item, 'toArray')
                            ? $item->toArray()
                            : $item;
                    },
                    $value
                );
            } else {
                $array[$this->camelToSnakeCase($propertyName)] = $value;
            }
        }

        return $array;
    }

    /**
     * Converts a snake_case string to camelCase format.
     *
     * @param string $string The input string in snake_case format.
     * @return string The converted string in camelCase format.
     */
    private function snakeToCamelCase(string $string): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    }

    /**
     * Converts a camelCase string to snake_case format.
     *
     * @param string $string The input string in camelCase format.
     * @return string The converted string in snake_case format.
     */
    private function camelToSnakeCase(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
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
            return $matches[1];
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
    public function clone()
    {
        return clone $this;
    }
}