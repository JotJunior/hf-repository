<?php

declare(strict_types=1);
/**
 * This file is part of hf-repository
 *
 * @link     https://github.com/JotJunior/hf-repository
 * @contact  hf-repository@jot.com.br
 * @license  MIT
 */

namespace Jot\HfRepository\Tests\Entity\Traits;

use Jot\HfRepository\Entity\Traits\EntityStateTrait;
use Jot\HfRepository\Exception\EntityValidationWithErrorsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(EntityStateTrait::class)]
class EntityStateTraitTest extends TestCase
{
    private EntityStateTraitTestClass $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new EntityStateTraitTestClass();
    }

    #[Test]
    #[Group('unit')]
    public function testGetEntityStateReturnsDefaultState(): void
    {
        // Act
        $result = $this->sut->getEntityState();

        // Assert
        $this->assertEquals(EntityStateTraitTestClass::STATE_CREATE, $result);
    }

    #[Test]
    #[Group('unit')]
    public function testGetEntityStateReturnsUpdatedState(): void
    {
        // Arrange
        $this->sut->setEntityState(EntityStateTraitTestClass::STATE_UPDATE);

        // Act
        $result = $this->sut->getEntityState();

        // Assert
        $this->assertEquals(EntityStateTraitTestClass::STATE_UPDATE, $result);
    }

    #[Test]
    #[Group('unit')]
    public function testSetEntityStateWithValidState(): void
    {
        // Arrange
        $state = EntityStateTraitTestClass::STATE_UPDATE;

        // Act
        $result = $this->sut->setEntityState($state);
        $retrievedState = $this->sut->getEntityState();

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertEquals($state, $retrievedState);
    }

    #[Test]
    #[Group('unit')]
    public function testSetEntityStateWithInvalidStateThrowsException(): void
    {
        // Arrange
        $invalidState = 'invalid_state';

        // Assert
        $this->expectException(EntityValidationWithErrorsException::class);

        // Act
        $this->sut->setEntityState($invalidState);
    }

    #[Test]
    #[Group('unit')]
    public function testSetEntityStateExceptionContainsErrorDetails(): void
    {
        // Arrange
        $invalidState = 'invalid_state';

        // Act
        try {
            $this->sut->setEntityState($invalidState);
            $this->fail('Expected exception was not thrown');
        } catch (EntityValidationWithErrorsException $e) {
            // Assert
            $errors = $e->getErrors();
            $this->assertArrayHasKey('entity_state', $errors);
            $this->assertStringContainsString('Invalid entity state', $errors['entity_state']);
        }
    }
}

/**
 * Test class that uses EntityStateTrait.
 */
class EntityStateTraitTestClass
{
    use EntityStateTrait;

    // Define constants here to avoid accessing trait constants directly
    public const STATE_CREATE = 'create';

    public const STATE_UPDATE = 'update';

    public function getEntityState(): string
    {
        return $this->entityState;
    }
}
