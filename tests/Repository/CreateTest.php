<?php

declare(strict_types=1);

namespace Jot\HfRepository\Tests\Repository;

use Jot\HfElastic\QueryBuilder;
use Jot\HfRepository\Adapter\QueryBuilderAdapter;
use Jot\HfRepository\Entity\EntityFactoryInterface;
use Jot\HfRepository\Entity\EntityInterface;
use Jot\HfRepository\Exception\RepositoryCreateException;
use Jot\HfRepository\Query\QueryParserInterface;
use Jot\HfRepository\Repository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Repository::class)]
class CreateTest extends TestCase
{
    private TestRepository $sut;
    private QueryBuilderAdapter $queryBuilderAdapter;
    private QueryParserInterface $queryParser;
    private EntityFactoryInterface $entityFactory;
    private TestEntity $testEntity;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->queryBuilderAdapter = $this->createMock(QueryBuilderAdapter::class);
        $this->queryParser = $this->createMock(QueryParserInterface::class);
        $this->entityFactory = $this->createMock(EntityFactoryInterface::class);
        $this->testEntity = $this->createMock(TestEntity::class);
        
        $this->sut = new TestRepository(
            $this->queryBuilderAdapter,
            $this->queryParser,
            $this->entityFactory
        );
    }

    #[Test]
    #[Group('unit')]
    public function testCreateThrowsExceptionWhenEntityFactoryReturnsNonEntity(): void
    {
        // Arrange
        $entityData = ['id' => 'new-id', 'name' => 'New Entity'];
        $resultData = ['id' => 'new-id', 'name' => 'New Entity', 'created_at' => '2025-03-04T12:00:00Z'];
        
        $this->testEntity->method('toArray')->willReturn($entityData);
        $this->testEntity->method('validate')->willReturn(true);
        
        $queryBuilderAdapterMock = $this->createMock(QueryBuilderAdapter::class);
        $queryBuilderAdapterMock->method('into')->willReturnSelf();
        $queryBuilderAdapterMock->method('insert')
            ->with($this->equalTo($entityData))
            ->willReturn([
                'result' => 'created',
                'data' => $resultData
            ]);
        
        // Substituir o adaptador no repositu00f3rio
        $reflectionProperty = new \ReflectionProperty($this->sut, 'queryBuilderAdapter');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->sut, $queryBuilderAdapterMock);
        
        // Return a non-entity object
        $nonEntity = new \stdClass();
        $this->entityFactory->method('create')
            ->with($this->equalTo(TestEntity::class), $this->equalTo(['data' => $resultData]))
            ->willReturn($nonEntity);
        
        // Assert
        $this->expectException(RepositoryCreateException::class);
        $this->expectExceptionMessage('Failed to create entity instance');
        
        // Act
        $this->sut->create($this->testEntity);
    }
}

/**
 * Concrete implementation of Repository for testing
 */
class TestRepository extends Repository
{
    protected string $entity = TestEntity::class;
}

/**
 * Test entity implementation for testing Repository
 */
class TestEntity implements EntityInterface
{
    private string $id;
    private string $name;
    private array $errors = [];
    
    public function __get(string $name): mixed
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        
        throw new \Exception("Property {$name} does not exist");
    }
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
    
    public function validate(): bool
    {
        return empty($this->errors);
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
}
