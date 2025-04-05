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

use Jot\HfRepository\Entity\Traits\ValidatableTrait;
use Jot\HfValidator\ValidatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ValidatableTrait::class)]
class ValidatableTraitTest extends TestCase
{
    private ValidatableTraitTestClass $sut;

    protected function setUp(): void
    {
        $this->sut = new ValidatableTraitTestClass();
        $this->sut->name = 'Test Name';
        $this->sut->email = 'test@example.com';
    }

    #[Test]
    #[Group('unit')]
    public function testAddValidator(): void
    {
        // Arrange
        $validator = $this->createMock(ValidatorInterface::class);

        // Act
        $this->sut->addValidator('name', $validator);

        // Assert
        $this->assertTrue($this->sut->hasValidator('name'));
    }

    #[Test]
    #[Group('unit')]
    public function testValidateWithValidData(): void
    {
        // Arrange
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with($this->sut->name)
            ->willReturn(true);

        $this->sut->addValidator('name', $validator);

        // Act
        $result = $this->sut->validate();

        // Assert
        $this->assertTrue($result);
        $this->assertEmpty($this->sut->getErrors());
    }

    #[Test]
    #[Group('unit')]
    public function testValidateWithInvalidData(): void
    {
        // Arrange
        $errors = ['Name is required'];

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with($this->sut->name)
            ->willReturn(false);
        $validator->expects($this->once())
            ->method('consumeErrors')
            ->willReturn($errors);

        $this->sut->addValidator('name', $validator);

        // Act
        $result = $this->sut->validate();

        // Assert
        $this->assertFalse($result);
        $this->assertEquals(['name' => $errors], $this->sut->getErrors());
    }

    #[Test]
    #[Group('unit')]
    public function testGetErrors(): void
    {
        // Arrange
        $errors = ['Name is required'];

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')
            ->willReturn(false);
        $validator->method('consumeErrors')
            ->willReturn($errors);

        $this->sut->addValidator('name', $validator);
        $this->sut->validate();

        // Act
        $result = $this->sut->getErrors();

        // Assert
        $this->assertEquals(['name' => $errors], $result);
    }
}

/**
 * Test class that uses ValidatableTrait.
 */
class ValidatableTraitTestClass
{
    use ValidatableTrait;

    public string $name;

    public string $email;

    public function getEntityState(): string
    {
        return 'create';
    }

    public function hasValidator(string $property): bool
    {
        return isset($this->validators[$property]) && ! empty($this->validators[$property]);
    }
}
