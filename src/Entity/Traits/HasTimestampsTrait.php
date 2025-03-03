<?php

namespace Jot\HfRepository\Entity\Traits;

use DateTimeInterface;

trait HasTimestampsTrait
{

    protected null|DateTimeInterface $createdAt = null;

    protected null|DateTimeInterface $updatedAt = null;


}