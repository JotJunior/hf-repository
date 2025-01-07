<?php

namespace Jot\HfRepository\Trait;

use DateTime;

trait HasTimestamps
{

    protected null|DateTime $createdAt = null;

    protected null|DateTime $updatedAt = null;


}