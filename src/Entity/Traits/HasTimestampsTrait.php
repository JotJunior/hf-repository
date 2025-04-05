<?php

declare(strict_types=1);
/**
 * This file is part of hf-repository
 *
 * @link     https://github.com/JotJunior/hf-repository
 * @contact  hf-repository@jot.com.br
 * @license  MIT
 */

namespace Jot\HfRepository\Entity\Traits;

use DateTimeInterface;

trait HasTimestampsTrait
{
    protected ?DateTimeInterface $createdAt = null;

    protected ?DateTimeInterface $updatedAt = null;
}
