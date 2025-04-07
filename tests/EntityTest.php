<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
 * @license  MIT
 */

namespace Jot\HfRepository\Tests;

use DateTime;
use Jot\HfRepository\Entity;
use Jot\HfRepository\Entity\EntityFactory;
use Jot\HfRepository\Entity\EntityFactoryInterface;
use Jot\HfRepository\Exception\EntityValidationWithErrorsException;
use Jot\HfRepository\Exception\InvalidEntityException;
use Jot\HfValidator\ValidatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @internal
 */
#[CoversClass(Entity::class)]
class EntityTest extends TestCase
{
    private TestEntity $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new TestEntity(['name' => 'Test Entity', 'email' => 'test@example.com']);
    }

    #[Test]
    #[Group('unit')]
    public function testConstructorHydratesEntity(): void
    {
        // Arrange
        $data = ['name' => 'New Entity', 'email' => 'new@example.com'];

        // Act
        $entity = new TestEntity($data);

        // Assert
        $this->assertEquals('New Entity', $entity->name);
        $this->assertEquals('new@example.com', $entity->email);
    }

    #[Test]
    #[Group('unit')]
    public function testMagicGetReturnsPropertyValue(): void
    {
        // Act & Assert
        $this->assertEquals('Test Entity', $this->sut->name);
        $this->assertEquals('test@example.com', $this->sut->email);
    }

    #[Test]
    #[Group('unit')]
    public function testMagicGetThrowsExceptionForNonExistentProperty(): void
    {
        // Arrange & Assert
        $this->expectException(InvalidEntityException::class);

        // Act
        $value = $this->sut->nonExistentProperty;
    }

    #[Test]
    #[Group('unit')]
    public function testGetAndSetId(): void
    {
        // Arrange
        $id = 'test-id-123';

        // Act
        $result = $this->sut->setId($id);

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertEquals($id, $this->sut->getId());
    }

    #[Test]
    #[Group('unit')]
    public function testSetAndGetEntityState(): void
    {
        // Arrange - default state should be STATE_CREATE
        $this->assertEquals(Entity::STATE_CREATE, $this->sut->getEntityStateForTest());

        // Act
        $result = $this->sut->setEntityState(Entity::STATE_UPDATE);

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertEquals(Entity::STATE_UPDATE, $this->sut->getEntityStateForTest());
    }

    #[Test]
    #[Group('unit')]
    public function testSetEntityStateThrowsExceptionForInvalidState(): void
    {
        // Arrange & Assert
        $this->expectException(EntityValidationWithErrorsException::class);

        // Act
        $this->sut->setEntityState('invalid_state');
    }

    #[Test]
    #[Group('unit')]
    public function testCreateHash(): void
    {
        // Arrange
        $property = 'email';
        $salt = 'test-salt';
        $encryptionKey = 'test-encryption-key';
        $expectedHash = hash_hmac('sha256', 'test@example.com' . $salt, $encryptionKey);

        // Act
        $result = $this->sut->createHash($property, $salt, $encryptionKey);

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertEquals($expectedHash, $this->sut->email);
    }

    #[Test]
    #[Group('unit')]
    public function testHideProperty(): void
    {
        // Arrange
        $reflectionClass = new ReflectionClass($this->sut);
        $method = $reflectionClass->getMethod('isHidden');
        $method->setAccessible(true);

        // Act
        $result = $this->sut->hide('name');
        $isHidden = $method->invoke($this->sut, 'name');

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertTrue($isHidden);
    }

    #[Test]
    #[Group('unit')]
    public function testHideMultipleProperties(): void
    {
        // Arrange
        $reflectionClass = new ReflectionClass($this->sut);
        $method = $reflectionClass->getMethod('isHidden');
        $method->setAccessible(true);

        // Act
        $result = $this->sut->hide(['name', 'email']);
        $isNameHidden = $method->invoke($this->sut, 'name');
        $isEmailHidden = $method->invoke($this->sut, 'email');

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertTrue($isNameHidden);
        $this->assertTrue($isEmailHidden);
    }

    #[Test]
    #[Group('unit')]
    public function testToArray(): void
    {
        // Arrange
        $this->sut->setId('test-id-123');

        // Act
        $result = $this->sut->toArray();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertEquals('test-id-123', $result['id']);
        $this->assertEquals('Test Entity', $result['name']);
        $this->assertEquals('test@example.com', $result['email']);
    }

    #[Test]
    #[Group('unit')]
    public function testToArrayHidesProperties(): void
    {
        // Arrange
        $this->sut->hide('email');

        // Act
        $result = $this->sut->toArray();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('email', $result);
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
    public function testValidateWithUpdateState(): void
    {
        // Arrange
        $this->sut->setEntityState(Entity::STATE_UPDATE);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')
            ->with($this->sut->name)
            ->willReturn(true);

        $this->sut->addValidator('name', $validator);

        // Act
        $result = $this->sut->validate();

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    #[Group('unit')]
    public function testHydrateWithSnakeCaseKeys(): void
    {
        // Arrange
        $data = ['user_name' => 'John Doe', 'user_email' => 'john@example.com'];

        // Act
        $entity = new TestEntityWithCamelCase($data);

        // Assert
        $this->assertEquals('John Doe', $entity->userName);
        $this->assertEquals('john@example.com', $entity->userEmail);
    }

    #[Test]
    #[Group('unit')]
    public function testToArrayWithDateTime(): void
    {
        // Arrange
        $now = new DateTime();
        $entity = new TestEntityWithDateTime(['created_at' => $now]);

        // Act
        $result = $entity->toArray();

        // Assert
        $this->assertArrayHasKey('created_at', $result);
        $this->assertEquals($now->format(DATE_ATOM), $result['created_at']);
    }

    #[Test]
    #[Group('unit')]
    public function testGetEntityFactoryCreatesDefaultFactory(): void
    {
        // Act
        $factory = $this->sut->getEntityFactory();

        // Assert
        $this->assertInstanceOf(EntityFactoryInterface::class, $factory);
        $this->assertInstanceOf(EntityFactory::class, $factory);
    }

    #[Test]
    #[Group('unit')]
    public function testSetEntityFactory(): void
    {
        // Arrange
        $mockFactory = $this->createMock(EntityFactoryInterface::class);

        // Act
        $result = $this->sut->setEntityFactory($mockFactory);
        $factory = $this->sut->getEntityFactory();

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertSame($mockFactory, $factory);
    }
}

/**
 * Test implementation of Entity for testing purposes.
 */
class TestEntity extends Entity
{
    protected string $name;

    protected string $email;

    /**
     * Get the entity state (for testing purposes).
     */
    public function getEntityStateForTest(): string
    {
        return $this->entityState;
    }
}

/**
 * Test implementation of Entity with camelCase properties.
 */
class TestEntityWithCamelCase extends Entity
{
    protected string $userName;

    protected string $userEmail;
}

/**
 * Test implementation of Entity with DateTime property.
 */
class TestEntityWithDateTime extends Entity
{
    protected DateTime $createdAt;
}
