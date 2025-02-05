<?php

namespace Jot\HfRepository\Trait;

use DateTimeInterface;

trait HasTimestamps
{

    protected null|DateTimeInterface $createdAt = null;

    protected null|DateTimeInterface $updatedAt = null;


}