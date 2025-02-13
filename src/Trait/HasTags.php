<?php

namespace Jot\HfRepository\Trait;

trait HasTags
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