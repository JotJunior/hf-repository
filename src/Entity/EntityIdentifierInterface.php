<?php

namespace Jot\HfRepository\Entity;

interface EntityIdentifierInterface
{

    public function getId(): ?string;

    public function setId(?string $id): self;

}