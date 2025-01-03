<?php

namespace Jot\HfRepository;

interface EntityInterface
{

    public function hydrate(array $data): void;

    public function toArray(array $data): array;

    public function getId(): ?string;

    public function clone(): self;
}