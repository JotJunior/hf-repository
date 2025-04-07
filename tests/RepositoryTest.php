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

use Hyperf\Context\Context;
use Jot\HfElastic\Contracts\QueryBuilderInterface;
use Jot\HfRepository\Entity\EntityFactoryInterface;
use Jot\HfRepository\Exception\EntityValidationWithErrorsException;
use Jot\HfRepository\Query\QueryParserInterface;
use Jot\HfRepository\Repository;
use Jot\HfRepository\Tests\Stubs\EntityFactoryStub;
use Jot\HfRepository\Tests\Stubs\RepositoryTestEntity;
use Jot\HfRepository\Tests\Stubs\SimpleQueryBuilder;
use Jot\HfRepository\Tests\Stubs\TestRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @internal
 */
#[CoversClass(Repository::class)]
class RepositoryTest extends TestCase
{
    // Constantes para facilitar os testes
    private const TEST_ID = 'test-id-123';

    private const TEST_NAME = 'Test Entity';

    private TestRepository $sut;

    private QueryBuilderInterface $queryBuilder;

    private QueryParserInterface $queryParser;

    private EntityFactoryInterface $entityFactory;

    private RepositoryTestEntity $testEntity;

    protected function setUp(): void
    {
        parent::setUp();

        // Usar a implementação simplificada do stub
        $this->queryBuilder = new SimpleQueryBuilder();

        $this->queryParser = $this->createMock(QueryParserInterface::class);
        $this->entityFactory = new EntityFactoryStub();
        $this->testEntity = new RepositoryTestEntity();
        $this->testEntity->setId('test-id-123')->setName('Test Entity');

        $this->sut = new TestRepository(
            $this->queryBuilder,
            $this->queryParser,
            $this->entityFactory
        );

        // Limpar o contexto antes de cada teste
        Context::set('repository.instance.find.tests.test-id-123', null);
        Context::set('repository.instance.first.tests.', null);
        Context::set('repository.instance.search.tests.', null);
        Context::set('repository.instance.paginate.tests.', null);
    }

    #[Test]
    #[Group('unit')]
    public function testGetIndexName(): void
    {
        // Use reflection to access protected method
        $reflectionClass = new ReflectionClass($this->sut);
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
        $entityData = ['id' => 'test-id-123', 'name' => 'Test Entity'];

        // Configure the query builder to return a successful result
        $queryBuilderStub = $this->queryBuilder;
        $queryBuilderStub->setSearchResults([$entityData]);

        // Act
        $result = $this->sut->find('test-id-123');

        // Assert
        $this->assertInstanceOf(RepositoryTestEntity::class, $result);
        $this->assertEquals('test-id-123', $result->getId());
        $this->assertEquals('Test Entity', $result->getName());
    }

    #[Test]
    #[Group('unit')]
    public function testFindReturnsNullWhenNotFound(): void
    {
        // Arrange
        $queryBuilderStub = $this->queryBuilder;
        $queryBuilderStub->setSearchResults([]);

        // Act
        $result = $this->sut->find('non-existent-id');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    #[Group('unit')]
    public function testFindReturnsCachedEntityWhenAvailable(): void
    {
        // Arrange
        $entity = new RepositoryTestEntity();
        $entity->setId('test-id-123')->setName('Cached Entity');

        // Store entity in context
        Context::set('repository.instance.find.tests.test-id-123', $entity);

        // Act
        $result = $this->sut->find('test-id-123');

        // Assert
        $this->assertSame($entity, $result);
        $this->assertEquals('Cached Entity', $result->getName());
    }

    #[Test]
    #[Group('unit')]
    public function testCreateReturnsEntityWhenSuccessful(): void
    {
        // Arrange
        $entity = new RepositoryTestEntity();
        $entity->setId('test-id-123')->setName('Test Entity');

        // Act
        $result = $this->sut->create($entity);

        // Assert
        $this->assertInstanceOf(RepositoryTestEntity::class, $result);
        $this->assertEquals('test-id-123', $result->getId());
        $this->assertEquals('Test Entity', $result->getName());
    }

    #[Test]
    #[Group('unit')]
    public function testCreateThrowsExceptionWhenValidationFails(): void
    {
        // Arrange
        $entity = new RepositoryTestEntity();
        $entity->setId('test-id-123')->setName('Test Entity');
        $entity->setValidationStatus(false, ['name' => 'Name is required']);

        // Assert & Act
        $this->expectException(EntityValidationWithErrorsException::class);
        $this->sut->create($entity);
    }

    #[Test]
    #[Group('unit')]
    public function testFirstReturnsEntityWhenFound(): void
    {
        // Arrange
        $entityData = ['id' => 'test-id-123', 'name' => 'Test Entity'];

        $queryBuilderStub = new SimpleQueryBuilder();
        $queryBuilderStub->setSearchResults([$entityData]);

        // Configurar o mock do queryParser para retornar o queryBuilderStub
        $this->queryParser = $this->createMock(QueryParserInterface::class);
        $this->queryParser->method('parse')->willReturn($queryBuilderStub);

        // Atualizar a instância do sut com o novo queryParser
        $this->sut = new TestRepository(
            $this->queryBuilder,
            $this->queryParser,
            $this->entityFactory
        );

        // Act
        $result = $this->sut->first(['name' => 'Test Entity']);

        // Assert
        $this->assertInstanceOf(RepositoryTestEntity::class, $result);
        $this->assertEquals('test-id-123', $result->getId());
        $this->assertEquals('Test Entity', $result->getName());
    }

    #[Test]
    #[Group('unit')]
    public function testFirstReturnsNullWhenNotFound(): void
    {
        // Arrange
        $queryBuilderStub = new SimpleQueryBuilder();
        $queryBuilderStub->setSearchResults([]);

        // Configurar o mock do queryParser para retornar o queryBuilderStub
        $this->queryParser = $this->createMock(QueryParserInterface::class);
        $this->queryParser->method('parse')->willReturn($queryBuilderStub);

        // Atualizar a instância do sut com o novo queryParser
        $this->sut = new TestRepository(
            $this->queryBuilder,
            $this->queryParser,
            $this->entityFactory
        );

        // Act
        $result = $this->sut->first(['name' => 'Non-existent Entity']);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    #[Group('unit')]
    public function testSearchReturnsEntitiesWhenFound(): void
    {
        // Arrange
        $entityData1 = ['id' => 'test-id-1', 'name' => 'Test Entity 1'];
        $entityData2 = ['id' => 'test-id-2', 'name' => 'Test Entity 2'];

        $queryBuilderStub = new SimpleQueryBuilder();
        $queryBuilderStub->setSearchResults([$entityData1, $entityData2]);

        // Configurar o mock do queryParser para retornar o queryBuilderStub
        $this->queryParser = $this->createMock(QueryParserInterface::class);
        $this->queryParser->method('parse')->willReturn($queryBuilderStub);

        // Atualizar a instância do sut com o novo queryParser
        $this->sut = new TestRepository(
            $this->queryBuilder,
            $this->queryParser,
            $this->entityFactory
        );

        // Act
        $results = $this->sut->search(['name' => 'Test']);

        // Assert
        $this->assertCount(2, $results);
        $this->assertInstanceOf(RepositoryTestEntity::class, $results[0]);
        $this->assertInstanceOf(RepositoryTestEntity::class, $results[1]);
        $this->assertEquals('test-id-1', $results[0]->getId());
        $this->assertEquals('test-id-2', $results[1]->getId());
    }

    #[Test]
    #[Group('unit')]
    public function testSearchReturnsEmptyArrayWhenNotFound(): void
    {
        // Arrange
        $queryBuilderStub = new SimpleQueryBuilder();
        $queryBuilderStub->setSearchResults([]);

        // Configurar o mock do queryParser para retornar o queryBuilderStub
        $this->queryParser = $this->createMock(QueryParserInterface::class);
        $this->queryParser->method('parse')->willReturn($queryBuilderStub);

        // Atualizar a instância do sut com o novo queryParser
        $this->sut = new TestRepository(
            $this->queryBuilder,
            $this->queryParser,
            $this->entityFactory
        );

        // Act
        $results = $this->sut->search(['name' => 'Non-existent']);

        // Assert
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    #[Test]
    #[Group('unit')]
    public function testPaginateReturnsFormattedResults(): void
    {
        // Arrange
        $entityData1 = ['id' => 'test-id-1', 'name' => 'Test Entity 1'];
        $entityData2 = ['id' => 'test-id-2', 'name' => 'Test Entity 2'];

        $queryBuilderStub = new SimpleQueryBuilder();
        $queryBuilderStub->setSearchResults([$entityData1, $entityData2]);
        $queryBuilderStub->setCountResult(10);

        // Configurar o mock do queryParser para retornar o queryBuilderStub
        $this->queryParser = $this->createMock(QueryParserInterface::class);
        $this->queryParser->method('parse')->willReturn($queryBuilderStub);

        // Atualizar a instância do sut com o novo queryParser
        $this->sut = new TestRepository(
            $this->queryBuilder,
            $this->queryParser,
            $this->entityFactory
        );

        // Act
        $result = $this->sut->paginate(['name' => 'Test'], 1, 10);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('current_page', $result);
        $this->assertArrayHasKey('per_page', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(2, $result['data']);
        $this->assertEquals(1, $result['current_page']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(10, $result['total']);
    }

    #[Test]
    #[Group('unit')]
    public function testUpdateReturnsUpdatedEntity(): void
    {
        // Arrange
        $entity = new RepositoryTestEntity();
        $entity->setId('test-id-123')->setName('Updated Entity');

        // Act
        $result = $this->sut->update($entity);

        // Assert
        $this->assertInstanceOf(RepositoryTestEntity::class, $result);
        $this->assertEquals('test-id-123', $result->getId());
        $this->assertEquals('Updated Entity', $result->getName());
    }

    #[Test]
    #[Group('unit')]
    public function testUpdateThrowsExceptionWhenValidationFails(): void
    {
        // Arrange
        $entity = new RepositoryTestEntity();
        $entity->setId('test-id-123')->setName('Updated Entity');
        $entity->setValidationStatus(false, ['name' => 'Name is invalid']);

        // Assert & Act
        $this->expectException(EntityValidationWithErrorsException::class);
        $this->sut->update($entity);
    }

    #[Test]
    #[Group('unit')]
    public function testDeleteReturnsTrueWhenSuccessful(): void
    {
        // Arrange
        $queryBuilderStub = $this->queryBuilder;
        $queryBuilderStub->setDeleteResult('deleted');

        // Act
        $result = $this->sut->delete('test-id-123');

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    #[Group('unit')]
    public function testDeleteReturnsFalseWhenFailed(): void
    {
        // Arrange
        $queryBuilderStub = $this->queryBuilder;
        $queryBuilderStub->setDeleteResult('error');

        // Act
        $result = $this->sut->delete('test-id-123');

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    #[Group('unit')]
    public function testExistsReturnsTrueWhenEntityExists(): void
    {
        // Arrange
        $queryBuilderStub = $this->queryBuilder;
        $queryBuilderStub->setCountResult(1);

        // Act
        $result = $this->sut->exists('test-id-123');

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    #[Group('unit')]
    public function testExistsReturnsFalseWhenEntityDoesNotExist(): void
    {
        // Arrange
        $queryBuilderStub = $this->queryBuilder;
        $queryBuilderStub->setCountResult(0);

        // Act
        $result = $this->sut->exists('non-existent-id');

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    #[Group('unit')]
    public function testExistsReturnsTrueWhenEntityInContext(): void
    {
        // Arrange
        $entity = new RepositoryTestEntity();
        $entity->setId('test-id-123')->setName('Cached Entity');

        // Store entity in context
        Context::set('repository.instance.find.tests.test-id-123', $entity);

        // Act
        $result = $this->sut->exists('test-id-123');

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    #[Group('unit')]
    public function testInvalidateContextCacheClearsCache(): void
    {
        // Arrange - Add items to context
        Context::set('repository.instance.search.tests.test-key', ['data']);
        Context::set('repository.instance.paginate.tests.test-key', ['data']);
        Context::set('repository.instance.first.tests.test-key', ['data']);

        // Use reflection to access protected method
        $reflectionClass = new ReflectionClass($this->sut);
        $method = $reflectionClass->getMethod('invalidateContextCache');
        $method->setAccessible(true);

        // Act
        $method->invoke($this->sut);

        // Assert
        $this->assertNull(Context::get('repository.instance.search.tests.test-key'));
        $this->assertNull(Context::get('repository.instance.paginate.tests.test-key'));
        $this->assertNull(Context::get('repository.instance.first.tests.test-key'));
    }
}
