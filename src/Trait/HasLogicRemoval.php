<?php

namespace Jot\HfRepository\Trait;

use DateTime;

trait HasLogicRemoval
{

    private bool $removed = false;

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function setRemoved(bool $removed = false): HasLogicRemoval
    {
        $this->removed = $removed;
        return $this;
    }


}