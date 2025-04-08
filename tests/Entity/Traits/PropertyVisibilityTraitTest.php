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

use Jot\HfRepository\Entity\Traits\PropertyVisibilityTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PropertyVisibilityTrait::class)]
class PropertyVisibilityTraitTest extends TestCase
{
    private PropertyVisibilityTraitTestClass $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new PropertyVisibilityTraitTestClass();
    }

    #[Test]
    #[Group('unit')]
    public function testDefaultHiddenProperties(): void
    {
        // Assert
        $this->assertIsArray($this->sut->getHiddenProperties());
        $this->assertContains('@timestamp', $this->sut->getHiddenProperties());
        $this->assertContains('deleted', $this->sut->getHiddenProperties());
        $this->assertContains('entity_state', $this->sut->getHiddenProperties());
        $this->assertContains('errors', $this->sut->getHiddenProperties());
        $this->assertContains('event_dispatcher', $this->sut->getHiddenProperties());
        $this->assertContains('hidden_properties', $this->sut->getHiddenProperties());
        $this->assertContains('logger', $this->sut->getHiddenProperties());
        $this->assertContains('validators', $this->sut->getHiddenProperties());
    }

    #[Test]
    #[Group('unit')]
    public function testHideWithStringProperty(): void
    {
        // Arrange
        $property = 'test_property';

        // Act
        $result = $this->sut->hide($property);

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertContains($property, $this->sut->getHiddenProperties());
    }

    #[Test]
    #[Group('unit')]
    public function testHideWithArrayOfProperties(): void
    {
        // Arrange
        $properties = ['property1', 'property2', 'property3'];

        // Act
        $result = $this->sut->hide($properties);

        // Assert
        $this->assertSame($this->sut, $result);
        foreach ($properties as $property) {
            $this->assertContains($property, $this->sut->getHiddenProperties());
        }
    }

    #[Test]
    #[Group('unit')]
    public function testIsHiddenWithHiddenProperty(): void
    {
        // Arrange
        $property = 'hidden_test_property';
        $this->sut->hide($property);

        // Act
        $result = $this->sut->isPropertyHidden($property);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    #[Group('unit')]
    public function testIsHiddenWithVisibleProperty(): void
    {
        // Arrange
        $property = 'visible_test_property';

        // Act
        $result = $this->sut->isPropertyHidden($property);

        // Assert
        $this->assertFalse($result);
    }
}

/**
 * Test class for PropertyVisibilityTrait.
 */
class PropertyVisibilityTraitTestClass
{
    use PropertyVisibilityTrait;

    public function getHiddenProperties(): array
    {
        return $this->hiddenProperties;
    }

    public function isPropertyHidden(string $property): bool
    {
        return $this->isHidden($property);
    }
}
