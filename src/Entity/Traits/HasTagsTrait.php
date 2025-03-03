<?php

namespace Jot\HfRepository\Entity\Traits;

trait HasTagsTrait
{

    protected null|array $tags = null;

    public function getTags(): null|array
    {
        return $this->tags;
    }

    public function addTag(string $tag): void
    {
        $this->tags[] = $tag;
    }

}