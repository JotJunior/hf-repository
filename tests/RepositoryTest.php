<?php

declare(strict_types=1);

namespace Jot\HfRepository\Tests;

use Hyperf\Stringable\Str;
use Jot\HfElastic\QueryBuilder;
use Jot\HfRepository\Entity\EntityFactoryInterface;
use Jot\HfRepository\Entity\EntityInterface;
use Jot\HfRepository\Exception\EntityValidationWithErrorsException;
use Jot\HfRepository\Exception\RepositoryCreateException;
use Jot\HfRepository\Exception\RepositoryUpdateException;
use Jot\HfRepository\Query\QueryParserInterface;
use Jot\HfRepository\Repository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Repository::class)]
class RepositoryTest extends TestCase
{
    private TestRepository $sut;
    private QueryBuilder $queryBuilder;
    private QueryParserInterface $queryParser;
    private EntityFactoryInterface $entityFactory;
    private RepositoryTestEntity $testEntity;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->queryParser = $this->createMock(QueryParserInterface::class);
        $this->entityFactory = $this->createMock(EntityFactoryInterface::class);
        $this->testEntity = $this->createMock(RepositoryTestEntity::class);
        
        $this->sut = new TestRepository(
            $this->queryBuilder,
            $this->queryParser,
            $this->entityFactory
        );
    }

    #[Test]
    #[Group('unit')]
    public function testGetIndexName(): void
    {
        // Use reflection to access protected method
        $reflectionClass = new \ReflectionClass($this->sut);
        $method = $reflectionClass->getMethod('getIndexName');
        $method->setAccessible(true);
        
        // Act
        $result = $method->invoke($this->sut);
        
        // Assert
        $this->assertEquals('tests', $result);
    }

    #[Test]
    #[Group('unit')]
    public function testFindReturnsEntityWhenFound(): void
    {
        // Arrange
        $id = 'test-id-123';
        $entityData = ['id' => $id, 'name' => 'Test Entity'];
        
        $queryMock = $this->createMock(QueryBuilder::class);
        $queryMock->method('select')->willReturnSelf();
        $queryMock->method('from')->willReturnSelf();
        $queryMock->method('where')->willReturnSelf();
        $queryMock->method('execute')->willReturn([
            'result' => 'success',
            'data' => [$entityData]
        ]);
        
        $this->queryBuilder->method('select')->willReturn($queryMock);
        
        $this->entityFactory->method('create')
            ->with($this->equalTo(RepositoryTestEntity::class), $this->equalTo(['data' => $entityData]))
            ->willReturn($this->testEntity);
        
        // Act
        $result = $this->sut->find($id);
        
        // Assert
        $this->assertSame($this->testEntity, $result);
    }

    #[Test]
    #[Group('unit')]
    public function testFindReturnsNullWhenNotFound(): void
    {
        // Arrange
        $id = 'non-existent-id';
        
        $queryMock = $this->createMock(QueryBuilder::class);
        $queryMock->method('select')->willReturnSelf();
        $queryMock->method('from')->willReturnSelf();
        $queryMock->method('where')->willReturnSelf();
        $queryMock->method('execute')->willReturn([
            'result' => 'success',
            'data' => []
        ]);
        
        $this->queryBuilder->method('select')->willReturn($queryMock);
        
        // Act
        $result = $this->sut->find($id);
        
        // Assert
        $this->assertNull($result);
    }

    #[Test]
    #[Group('unit')]
    public function testCreateSuccessful(): void
    {
        // Arrange
        $entityData = ['id' => 'new-id', 'name' => 'New Entity'];
        $resultData = ['id' => 'new-id', 'name' => 'New Entity', 'created_at' => '2025-03-04T12:00:00Z'];
        
        $this->testEntity->method('toArray')->willReturn($entityData);
        $this->testEntity->method('validate')->willReturn(true);
        
        $queryMock = $this->createMock(QueryBuilder::class);
        $queryMock->method('into')->willReturnSelf();
        $queryMock->method('insert')->willReturn([
            'result' => 'created',
            'data' => $resultData
        ]);
        
        $this->queryBuilder->method('into')->willReturn($queryMock);
        
        $createdEntity = $this->createMock(RepositoryTestEntity::class);
        $this->entityFactory->method('create')
            ->with($this->equalTo(RepositoryTestEntity::class), $this->equalTo(['data' => $resultData]))
            ->willReturn($createdEntity);
        
        // Act
        $result = $this->sut->create($this->testEntity);
        
        // Assert
        $this->assertSame($createdEntity, $result);
    }

    #[Test]
    #[Group('unit')]
    public function testCreateThrowsExceptionWhenValidationFails(): void
    {
        // Arrange
        $errors = ['name' => 'Name is required'];
        $this->testEntity->method('validate')->willReturn(false);
        $this->testEntity->method('getErrors')->willReturn($errors);
        
        // Assert
        $this->expectException(EntityValidationWithErrorsException::class);
        
        // Act
        $this->sut->create($this->testEntity);
    }

    #[Test]
    #[Group('unit')]
    public function testCreateThrowsExceptionWhenRepositoryFails(): void
    {
        // Arrange
        $entityData = ['id' => 'new-id', 'name' => 'New Entity'];
        
        $this->testEntity->method('toArray')->willReturn($entityData);
        $this->testEntity->method('validate')->willReturn(true);
        
        $queryMock = $this->createMock(QueryBuilder::class);
        $queryMock->method('into')->willReturnSelf();
        $queryMock->method('insert')->willReturn([
            'result' => 'error',
            'error' => 'Database connection failed'
        ]);
        
        $this->queryBuilder->method('into')->willReturn($queryMock);
        
        // Assert
        $this->expectException(RepositoryCreateException::class);
        
        // Act
        $this->sut->create($this->testEntity);
    }

    #[Test]
    #[Group('unit')]
    public function testFirstReturnsEntityWhenFound(): void
    {
        // Arrange
        $params = ['name' => 'Test'];
        $entityData = ['id' => 'test-id', 'name' => 'Test Entity'];
        
        $queryMock = $this->createMock(QueryBuilder::class);
        $queryMock->method('limit')->willReturnSelf();
        $queryMock->method('execute')->willReturn([
            'result' => 'success',
            'data' => [$entityData]
        ]);
        
        $this->queryBuilder->method('from')->willReturnSelf();
        $this->queryParser->method('parse')->willReturn($queryMock);
        
        $this->entityFactory->method('create')
            ->with($this->equalTo(RepositoryTestEntity::class), $this->equalTo(['data' => $entityData]))
            ->willReturn($this->testEntity);
        
        // Act
        $result = $this->sut->first($params);
        
        // Assert
        $this->assertSame($this->testEntity, $result);
    }

    #[Test]
    #[Group('unit')]
    public function testFirstReturnsNullWhenNotFound(): void
    {
        // Arrange
        $params = ['name' => 'NonExistent'];
        
        $queryMock = $this->createMock(QueryBuilder::class);
        $queryMock->method('limit')->willReturnSelf();
        $queryMock->method('execute')->willReturn([
            'result' => 'success',
            'data' => []
        ]);
        
        $this->queryBuilder->method('from')->willReturnSelf();
        $this->queryParser->method('parse')->willReturn($queryMock);
        
        // Act
        $result = $this->sut->first($params);
        
        // Assert
        $this->assertNull($result);
    }

    #[Test]
    #[Group('unit')]
    public function testSearchReturnsEntitiesWhenFound(): void
    {
        // Arrange
        $params = ['name' => 'Test'];
        $entityData1 = ['id' => 'test-id-1', 'name' => 'Test Entity 1'];
        $entityData2 = ['id' => 'test-id-2', 'name' => 'Test Entity 2'];
        
        $queryMock = $this->createMock(QueryBuilder::class);
        $queryMock->method('execute')->willReturn([
            'result' => 'success',
            'data' => [$entityData1, $entityData2]
        ]);
        
        $this->queryBuilder->method('from')->willReturnSelf();
        $this->queryParser->method('parse')->willReturn($queryMock);
        
        $entity1 = $this->createMock(RepositoryTestEntity::class);
        $entity2 = $this->createMock(RepositoryTestEntity::class);
        
        $this->entityFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [RepositoryTestEntity::class, ['data' => $entityData1], $entity1],
                [RepositoryTestEntity::class, ['data' => $entityData2], $entity2]
            ]);
        
        // Act
        $result = $this->sut->search($params);
        
        // Assert
        $this->assertCount(2, $result);
        $this->assertContains($entity1, $result);
        $this->assertContains($entity2, $result);
    }

    #[Test]
    #[Group('unit')]
    public function testSearchReturnsEmptyArrayWhenNotFound(): void
    {
        // Arrange
        $params = ['name' => 'NonExistent'];
        
        $queryMock = $this->createMock(QueryBuilder::class);
        $queryMock->method('execute')->willReturn([
            'result' => 'success',
            'data' => []
        ]);
        
        $this->queryBuilder->method('from')->willReturnSelf();
        $this->queryParser->method('parse')->willReturn($queryMock);
        
        // Act
        $result = $this->sut->search($params);
        
        // Assert
        $this->assertEmpty($result);
    }

    #[Test]
    #[Group('unit')]
    public function testPaginateReturnsFormattedResults(): void
    {
        // Arrange
        $params = ['name' => 'Test'];
        $page = 2;
        $perPage = 5;
        $entityData1 = ['id' => 'test-id-1', 'name' => 'Test Entity 1'];
        $entityData2 = ['id' => 'test-id-2', 'name' => 'Test Entity 2'];
        
        $queryMock = $this->createMock(QueryBuilder::class);
        $queryMock->method('limit')->willReturnSelf();
        $queryMock->method('offset')->willReturnSelf();
        $queryMock->method('execute')->willReturn([
            'result' => 'success',
            'data' => [$entityData1, $entityData2]
        ]);
        $queryMock->method('count')->willReturn(15); // Total count
        
        $this->queryBuilder->method('from')->willReturnSelf();
        $this->queryParser->method('parse')->willReturn($queryMock);
        
        $entity1 = $this->createMock(RepositoryTestEntity::class);
        $entity1->method('toArray')->willReturn($entityData1);
        $entity2 = $this->createMock(RepositoryTestEntity::class);
        $entity2->method('toArray')->willReturn($entityData2);
        
        $this->entityFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [RepositoryTestEntity::class, ['data' => $entityData1], $entity1],
                [RepositoryTestEntity::class, ['data' => $entityData2], $entity2]
            ]);
        
        // Act
        $result = $this->sut->paginate($params, $page, $perPage);
        
        // Assert
        $this->assertEquals('success', $result['result']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals($entityData1, $result['data'][0]);
        $this->assertEquals($entityData2, $result['data'][1]);
        $this->assertEquals($page, $result['current_page']);
        $this->assertEquals($perPage, $result['per_page']);
        $this->assertEquals(15, $result['total']);
    }

    #[Test]
    #[Group('unit')]
    public function testUpdateSuccessful(): void
    {
        // Arrange
        $id = 'test-id-123';
        $entityData = ['id' => $id, 'name' => 'Updated Entity'];
        $resultData = ['id' => $id, 'name' => 'Updated Entity', 'updated_at' => '2025-03-04T12:00:00Z'];
        
        $this->testEntity->method('toArray')->willReturn($entityData);
        $this->testEntity->method('validate')->willReturn(true);
        $this->testEntity->method('getId')->willReturn($id);
        
        $queryMock = $this->createMock(QueryBuilder::class);
        $queryMock->method('from')->willReturnSelf();
        $queryMock->method('update')->willReturn([
            'result' => 'updated',
            'data' => $resultData
        ]);
        
        $this->queryBuilder->method('from')->willReturn($queryMock);
        
        $updatedEntity = $this->createMock(RepositoryTestEntity::class);
        $this->entityFactory->method('create')
            ->with($this->equalTo(RepositoryTestEntity::class), $this->equalTo(['data' => $resultData]))
            ->willReturn($updatedEntity);
        
        // Act
        $result = $this->sut->update($this->testEntity);
        
        // Assert
        $this->assertSame($updatedEntity, $result);
    }

    #[Test]
    #[Group('unit')]
    public function testUpdateThrowsExceptionWhenValidationFails(): void
    {
        // Arrange
        $errors = ['name' => 'Name is required'];
        $this->testEntity->method('validate')->willReturn(false);
        $this->testEntity->method('getErrors')->willReturn($errors);
        
        // Assert
        $this->expectException(EntityValidationWithErrorsException::class);
        
        // Act
        $this->sut->update($this->testEntity);
    }

    #[Test]
    #[Group('unit')]
    public function testUpdateThrowsExceptionWhenRepositoryFails(): void
    {
        // Arrange
        $id = 'test-id-123';
        $entityData = ['id' => $id, 'name' => 'Updated Entity'];
        
        $this->testEntity->method('toArray')->willReturn($entityData);
        $this->testEntity->method('validate')->willReturn(true);
        $this->testEntity->method('getId')->willReturn($id);
        
        $queryMock = $this->createMock(QueryBuilder::class);
        $queryMock->method('from')->willReturnSelf();
        $queryMock->method('update')->willReturn([
            'result' => 'error',
            'error' => 'Database connection failed'
        ]);
        
        $this->queryBuilder->method('from')->willReturn($queryMock);
        
        // Assert
        $this->expectException(RepositoryUpdateException::class);
        
        // Act
        $this->sut->update($this->testEntity);
    }

    #[Test]
    #[Group('unit')]
    public function testDeleteSuccessful(): void
    {
        // Arrange
        $id = 'test-id-123';
        
        $queryMock = $this->createMock(QueryBuilder::class);
        $queryMock->method('from')->willReturnSelf();
        $queryMock->method('delete')->willReturn([
            'result' => 'deleted'
        ]);
        
        $this->queryBuilder->method('from')->willReturn($queryMock);
        
        // Act
        $result = $this->sut->delete($id);
        
        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    #[Group('unit')]
    public function testDeleteReturnsFalseWhenFailed(): void
    {
        // Arrange
        $id = 'test-id-123';
        
        $queryMock = $this->createMock(QueryBuilder::class);
        $queryMock->method('from')->willReturnSelf();
        $queryMock->method('delete')->willReturn([
            'result' => 'error'
        ]);
        
        $this->queryBuilder->method('from')->willReturn($queryMock);
        
        // Act
        $result = $this->sut->delete($id);
        
        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    #[Group('unit')]
    public function testExistsReturnsTrue(): void
    {
        // Arrange
        $id = 'test-id-123';
        
        $queryMock = $this->createMock(QueryBuilder::class);
        $queryMock->method('select')->willReturnSelf();
        $queryMock->method('from')->willReturnSelf();
        $queryMock->method('where')->willReturnSelf();
        $queryMock->method('count')->willReturn(1);
        
        $this->queryBuilder->method('select')->willReturn($queryMock);
        
        // Act
        $result = $this->sut->exists($id);
        
        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    #[Group('unit')]
    public function testExistsReturnsFalse(): void
    {
        // Arrange
        $id = 'non-existent-id';
        
        $queryMock = $this->createMock(QueryBuilder::class);
        $queryMock->method('select')->willReturnSelf();
        $queryMock->method('from')->willReturnSelf();
        $queryMock->method('where')->willReturnSelf();
        $queryMock->method('count')->willReturn(0);
        
        $this->queryBuilder->method('select')->willReturn($queryMock);
        
        // Act
        $result = $this->sut->exists($id);
        
        // Assert
        $this->assertFalse($result);
    }
}

/**
 * Concrete implementation of Repository for testing
 */
class TestRepository extends Repository
{
    protected string $entity = RepositoryTestEntity::class;
}

/**
 * Test entity implementation for testing Repository
 */
class RepositoryTestEntity implements EntityInterface
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
