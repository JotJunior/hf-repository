<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
 * @license  MIT
 */

namespace Jot\HfRepository\Tests\Entity\Traits;

use Jot\HfRepository\Entity\Traits\EntityIdentifierTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(EntityIdentifierTrait::class)]
class EntityIdentifierTraitTest extends TestCase
{
    private EntityIdentifierTraitTestClass $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new EntityIdentifierTraitTestClass();
    }

    #[Test]
    #[Group('unit')]
    public function testGetIdReturnsNullByDefault(): void
    {
        // Act
        $result = $this->sut->getId();

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    #[Group('unit')]
    public function testSetIdUpdatesIdValue(): void
    {
        // Arrange
        $id = 'test-id-123';

        // Act
        $result = $this->sut->setId($id);
        $retrievedId = $this->sut->getId();

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertEquals($id, $retrievedId);
    }

    #[Test]
    #[Group('unit')]
    public function testSetIdWithNullClearsId(): void
    {
        // Arrange
        $this->sut->setId('test-id-123');

        // Act
        $result = $this->sut->setId(null);
        $retrievedId = $this->sut->getId();

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertNull($retrievedId);
    }
}

/**
 * Test class that uses EntityIdentifierTrait.
 */
class EntityIdentifierTraitTestClass
{
    use EntityIdentifierTrait;
}
