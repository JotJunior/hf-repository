<?php

namespace Jot\HfRepository\Tests\Entity\Traits;

use Jot\HfRepository\Entity\Traits\HydratableTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Hyperf\Swagger\Annotation as SA;

#[CoversClass(HydratableTrait::class)]
class HydratableTraitScalarTest extends TestCase
{
    private HydratableTraitScalarTestClass $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new HydratableTraitScalarTestClass();
    }

    #[Test]
    #[Group('unit')]
    public function testHydrateWithScalarValueForRelatedClass(): void
    {
        // Arrange
        $data = [
            'related_entity' => 123 // Scalar value
        ];

        // Act
        $result = $this->sut->hydrate($data);

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertInstanceOf(RelatedEntityWithHydrate::class, $this->sut->relatedEntity);
        $this->assertEquals(123, $this->sut->relatedEntity->id);
    }

    #[Test]
    #[Group('unit')]
    public function testHydrateWithoutEntityFactoryUsingArray(): void
    {
        // Arrange
        $data = [
            'related_entity' => [
                'id' => 456,
                'name' => 'Test Name'
            ]
        ];

        // Act
        $result = $this->sut->hydrate($data);

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertInstanceOf(RelatedEntityWithHydrate::class, $this->sut->relatedEntity);
        $this->assertEquals(456, $this->sut->relatedEntity->id);
        $this->assertEquals('Test Name', $this->sut->relatedEntity->name);
    }

    #[Test]
    #[Group('unit')]
    public function testHydrateWithoutEntityFactoryUsingScalar(): void
    {
        // Arrange
        $data = [
            'related_entity_without_hydrate' => 789 // Scalar value for a class without hydrate
        ];

        // Act
        $result = $this->sut->hydrate($data);

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertInstanceOf(RelatedEntityWithoutHydrate::class, $this->sut->relatedEntityWithoutHydrate);
        // The scalar value should be ignored since the class doesn't have a hydrate method
        $this->assertNull($this->sut->relatedEntityWithoutHydrate->id);
    }
}

/**
 * Test class for HydratableTrait with scalar values
 */
class HydratableTraitScalarTestClass
{
    use HydratableTrait;

    #[SA\Property(x: ['php_type' => RelatedEntityWithHydrate::class])]
    public ?RelatedEntityWithHydrate $relatedEntity = null;

    #[SA\Property(x: ['php_type' => RelatedEntityWithoutHydrate::class])]
    public ?RelatedEntityWithoutHydrate $relatedEntityWithoutHydrate = null;

    /**
     * Gets the entity factory used to create related entities.
     * 
     * @return null Always returns null to force direct instantiation
     */
    public function getEntityFactory(): ?object
    {
        return null; // Force direct instantiation path
    }
}

/**
 * Related entity with hydrate method for testing
 */
class RelatedEntityWithHydrate
{
    public ?int $id = null;
    public ?string $name = null;
    
    public function __construct(array $data = [])
    {
        $this->hydrate($data);
    }
    
    public function hydrate(array $data): self
    {
        if (isset($data['id'])) {
            $this->id = $data['id'];
        }
        
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }
        
        return $this;
    }
}

/**
 * Related entity without hydrate method for testing
 */
class RelatedEntityWithoutHydrate
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
