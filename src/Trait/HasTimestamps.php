<?php

namespace Jot\HfRepository\Trait;

use DateTime;

trait HasTimestamps
{

    protected null|string|DateTime $createdAt = null;
    protected null|string|DateTime $updatedAt = null;

    public function getCreatedAt(): DateTime|string|null
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime|string|null $createdAt = null): HasTimestamps
    {
        if (is_string($createdAt)) {
            $createdAt = new DateTime($createdAt ?? 'now');
        }
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): DateTime|string|null
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime|string|null $updatedAt = null): HasTimestamps
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }


}