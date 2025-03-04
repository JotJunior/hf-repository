<?php

declare(strict_types=1);

namespace Jot\HfRepository\Tests\Entity;

use Jot\HfRepository\Entity\EntityFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EntityFactory::class)]
class EntityFactoryTest extends TestCase
{
    private EntityFactory $sut;

    #[Test]
    #[Group('unit')]
    public function testCreate(): void
    {
        // Arrange
        $data = [
            'id' => 123,
            'name' => 'Test Entity'
        ];

        // Act
        $entity = $this->sut->create(TestEntity::class, $data);

        // Assert
        $this->assertInstanceOf(TestEntity::class, $entity);
        $this->assertEquals(123, $entity->id);
        $this->assertEquals('Test Entity', $entity->name);
    }

    #[Test]
    #[Group('unit')]
    public function testInvoke(): void
    {
        // Arrange
        $data = [
            'id' => 456,
            'name' => 'Invoked Entity'
        ];

        // Act - using the factory as a function
        $entity = ($this->sut)(TestEntity::class, $data);

        // Assert
        $this->assertInstanceOf(TestEntity::class, $entity);
        $this->assertEquals(456, $entity->id);
        $this->assertEquals('Invoked Entity', $entity->name);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new EntityFactory();
    }
}

/**
 * Simple entity class for testing EntityFactory
 */
class TestEntity
{
    public ?int $id = null;
    public ?string $name = null;
    
    public function __construct(array $data = [])
    {
        if (isset($data['id'])) {
            $this->id = $data['id'];
        }
        
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }
    }
}
