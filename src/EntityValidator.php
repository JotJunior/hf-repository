<?php

namespace Jot\HfRepository;

class EntityValidator
{

    private static array $validators = [];

    static public function addValidator(string $entity, string $property, ?object $validator): void
    {
        self::$validators[$entity][$property][] = $validator;
    }

    static public function list(string $entity): array
    {
        return self::$validators[$entity] ?? [];
    }

    static public function has(string $entity, string $property): bool
    {
        return isset(self::$validators[$entity][$property]);
    }

}