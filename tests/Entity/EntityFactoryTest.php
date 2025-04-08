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

namespace Jot\HfRepository\Tests\Entity;

use Jot\HfRepository\Entity\EntityFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(EntityFactory::class)]
class EntityFactoryTest extends TestCase
{
    private EntityFactory $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new EntityFactory();
    }

    #[Test]
    #[Group('unit')]
    public function testCreate(): void
    {
        // Arrange
        $data = [
            'id' => 123,
            'name' => 'Test Entity',
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
    public function testCreateWithIndividualParameters(): void
    {
        // Arrange
        $data = [
            'id' => 789,
            'name' => 'Individual Params Entity',
        ];

        // Act - this will use the non-array constructor path
        $entity = $this->sut->create(TestEntityWithIndividualParams::class, $data);

        // Assert
        $this->assertInstanceOf(TestEntityWithIndividualParams::class, $entity);
        $this->assertEquals(789, $entity->id);
        $this->assertEquals('Individual Params Entity', $entity->name);
    }
}

/**
 * Simple entity class for testing EntityFactory.
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

/**
 * Entity class with individual constructor parameters for testing.
 */
class TestEntityWithIndividualParams
{
    public ?int $id = null;

    public ?string $name = null;

    /**
     * Constructor that accepts individual parameters instead of an array.
     * @param null|mixed $id
     * @param null|mixed $name
     */
    public function __construct($id = null, $name = null)
    {
        $this->id = $id;
        $this->name = $name;
    }
}
