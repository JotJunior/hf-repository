<?php

declare(strict_types=1);

namespace Jot\HfRepository\Tests\Query;

use Jot\HfElastic\QueryBuilder;
use Jot\HfRepository\Query\QueryParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(QueryParser::class)]
class QueryParserTest extends TestCase
{
    private QueryParser $sut;
    private QueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->sut = new QueryParser();
    }

    #[Test]
    #[Group('unit')]
    public function testParseWithEmptyParams(): void
    {
        // Arrange
        $params = [];
        
        // Setup expectations
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with(['*'])
            ->willReturnSelf();
        
        // Act
        $result = $this->sut->parse($params, $this->queryBuilder);
        
        // Assert
        $this->assertSame($this->queryBuilder, $result);
    }
    
    #[Test]
    #[Group('unit')]
    public function testParseWithFieldsParameter(): void
    {
        // Arrange
        $params = ['_fields' => 'id,name,email'];
        
        // Setup expectations
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with(['id', 'name', 'email'])
            ->willReturnSelf();
        
        // Act
        $result = $this->sut->parse($params, $this->queryBuilder);
        
        // Assert
        $this->assertSame($this->queryBuilder, $result);
    }
    
    #[Test]
    #[Group('unit')]
    public function testParseWithSortParameter(): void
    {
        // Arrange
        $params = ['_sort' => 'name:asc,created_at:desc'];
        
        // Setup expectations
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with(['*'])
            ->willReturnSelf();
            
        // We'll use a callback to verify the arguments for each call
        $orderByCallCount = 0;
        $expectedOrderByArgs = [
            ['name', 'asc'],
            ['created_at', 'desc']
        ];
        
        $self = $this;
        $this->queryBuilder->expects($this->exactly(2))
            ->method('orderBy')
            ->willReturnCallback(function ($field, $direction) use (&$orderByCallCount, $expectedOrderByArgs, $self) {
                $self->assertEquals($expectedOrderByArgs[$orderByCallCount][0], $field, "Unexpected field for orderBy call {$orderByCallCount}");
                $self->assertEquals($expectedOrderByArgs[$orderByCallCount][1], $direction, "Unexpected direction for orderBy call {$orderByCallCount}");
                $orderByCallCount++;
                return $self->queryBuilder;
            });
        
        // Act
        $result = $this->sut->parse($params, $this->queryBuilder);
        
        // Assert
        $this->assertSame($this->queryBuilder, $result);
    }
    
    #[Test]
    #[Group('unit')]
    public function testParseWithSortParameterDefaultDirection(): void
    {
        // Arrange
        $params = ['_sort' => 'name'];
        
        // Setup expectations
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with(['*'])
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('name', 'asc')
            ->willReturnSelf();
        
        // Act
        $result = $this->sut->parse($params, $this->queryBuilder);
        
        // Assert
        $this->assertSame($this->queryBuilder, $result);
    }
    
    #[Test]
    #[Group('unit')]
    public function testParseWithFilterParameters(): void
    {
        // Arrange
        $params = [
            'name' => 'John',
            'age' => 30,
            'active' => true
        ];
        
        // Setup expectations
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with(['*'])
            ->willReturnSelf();
            
        // We'll use a callback to verify the arguments for each call
        $whereCallCount = 0;
        $expectedWhereArgs = [
            ['name', '=', 'John'],
            ['age', '=', 30],
            ['active', '=', true]
        ];
        
        $self = $this;
        $this->queryBuilder->expects($this->exactly(3))
            ->method('where')
            ->willReturnCallback(function ($field, $operator, $value) use (&$whereCallCount, $expectedWhereArgs, $self) {
                $self->assertEquals($expectedWhereArgs[$whereCallCount][0], $field, "Unexpected field for where call {$whereCallCount}");
                $self->assertEquals($expectedWhereArgs[$whereCallCount][1], $operator, "Unexpected operator for where call {$whereCallCount}");
                $self->assertEquals($expectedWhereArgs[$whereCallCount][2], $value, "Unexpected value for where call {$whereCallCount}");
                $whereCallCount++;
                return $self->queryBuilder;
            });
        
        // Act
        $result = $this->sut->parse($params, $this->queryBuilder);
        
        // Assert
        $this->assertSame($this->queryBuilder, $result);
    }
    
    #[Test]
    #[Group('unit')]
    public function testParseWithMixedParameters(): void
    {
        // Arrange
        $params = [
            '_fields' => 'id,name,email',
            '_sort' => 'name:asc,created_at:desc',
            'status' => 'active',
            'category' => 'customer'
        ];
        
        // Setup expectations
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with(['id', 'name', 'email'])
            ->willReturnSelf();
            
        // We'll use a callback to verify the arguments for each orderBy call
        $orderByCallCount = 0;
        $expectedOrderByArgs = [
            ['name', 'asc'],
            ['created_at', 'desc']
        ];
        
        $self = $this;
        $this->queryBuilder->expects($this->exactly(2))
            ->method('orderBy')
            ->willReturnCallback(function ($field, $direction) use (&$orderByCallCount, $expectedOrderByArgs, $self) {
                $self->assertEquals($expectedOrderByArgs[$orderByCallCount][0], $field, "Unexpected field for orderBy call {$orderByCallCount}");
                $self->assertEquals($expectedOrderByArgs[$orderByCallCount][1], $direction, "Unexpected direction for orderBy call {$orderByCallCount}");
                $orderByCallCount++;
                return $self->queryBuilder;
            });
            
        // We'll use a callback to verify the arguments for each where call
        $whereCallCount = 0;
        $expectedWhereArgs = [
            ['status', '=', 'active'],
            ['category', '=', 'customer']
        ];
        
        $self = $this;
        $this->queryBuilder->expects($this->exactly(2))
            ->method('where')
            ->willReturnCallback(function ($field, $operator, $value) use (&$whereCallCount, $expectedWhereArgs, $self) {
                $self->assertEquals($expectedWhereArgs[$whereCallCount][0], $field, "Unexpected field for where call {$whereCallCount}");
                $self->assertEquals($expectedWhereArgs[$whereCallCount][1], $operator, "Unexpected operator for where call {$whereCallCount}");
                $self->assertEquals($expectedWhereArgs[$whereCallCount][2], $value, "Unexpected value for where call {$whereCallCount}");
                $whereCallCount++;
                return $self->queryBuilder;
            });
        
        // Act
        $result = $this->sut->parse($params, $this->queryBuilder);
        
        // Assert
        $this->assertSame($this->queryBuilder, $result);
    }
    
    #[Test]
    #[Group('unit')]
    public function testParseIgnoresUnderscorePrefixedParameters(): void
    {
        // Arrange
        $params = [
            '_fields' => 'id,name',
            '_sort' => 'name:asc',
            '_page' => 1,
            '_limit' => 10,
            'status' => 'active'
        ];
        
        // Setup expectations
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with(['id', 'name'])
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('name', 'asc')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('status', '=', 'active')
            ->willReturnSelf();
        
        // Act
        $result = $this->sut->parse($params, $this->queryBuilder);
        
        // Assert
        $this->assertSame($this->queryBuilder, $result);
    }
}
