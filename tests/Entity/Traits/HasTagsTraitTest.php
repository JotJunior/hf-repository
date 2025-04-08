<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for
 * manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
 * @license  MIT
 */

namespace Jot\HfRepository\Tests\Entity\Traits;

use Jot\HfRepository\Entity\Traits\HasTagsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(HasTagsTrait::class)]
class HasTagsTraitTest extends TestCase
{
    private HasTagsTraitTestClass $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new HasTagsTraitTestClass();
    }

    #[Test]
    #[Group('unit')]
    public function testGetTagsReturnsNullByDefault(): void
    {
        // Act
        $result = $this->sut->getTags();

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    #[Group('unit')]
    public function testAddTagWithEmptyTags(): void
    {
        // Arrange
        $tag = 'test-tag';

        // Act
        $this->sut->addTag($tag);

        // Assert
        $this->assertIsArray($this->sut->getTags());
        $this->assertCount(1, $this->sut->getTags());
        $this->assertContains($tag, $this->sut->getTags());
    }

    #[Test]
    #[Group('unit')]
    public function testAddTagWithExistingTags(): void
    {
        // Arrange
        $firstTag = 'first-tag';
        $secondTag = 'second-tag';

        // Act
        $this->sut->addTag($firstTag);
        $this->sut->addTag($secondTag);

        // Assert
        $this->assertIsArray($this->sut->getTags());
        $this->assertCount(2, $this->sut->getTags());
        $this->assertContains($firstTag, $this->sut->getTags());
        $this->assertContains($secondTag, $this->sut->getTags());
    }
}

/**
 * Test class for HasTagsTrait.
 */
class HasTagsTraitTestClass
{
    use HasTagsTrait;
}
