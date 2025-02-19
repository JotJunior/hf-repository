<?php

namespace Jot\HfRepository;

interface EntityInterface
{

    public function hydrate(array $data): self;

    public function toArray(): array;

    public function clone(): self;

    public function getId(): ?string;

    public function hide(array|string $property): self;

    public function getErrors(): array;

}