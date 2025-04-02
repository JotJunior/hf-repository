<?php

declare(strict_types=1);

namespace Jot\HfRepository\Tests\Stubs;

use Jot\HfElastic\Contracts\QueryBuilderInterface;

/**
 * Stub implementation of QueryBuilderInterface for testing.
 */
class QueryBuilderStub implements QueryBuilderInterface
{
    /**
     * @var array
     */
    private array $mockResults = [
        'select' => [
            'result' => 'success',
            'data' => [['data' => ['id' => 'test-id-123', 'name' => 'Test Entity']]]
        ],
        'insert' => [
            'result' => 'created',
            'id' => 'test-id-123',
            'data' => ['id' => 'test-id-123', 'name' => 'Test Entity']
        ],
        'update' => [
            'result' => 'updated',
            'affected' => 1,
            'data' => ['id' => 'test-id-123', 'name' => 'Test Entity']
        ],
        'delete' => [
            'result' => 'deleted',
            'affected' => 1
        ],
        'count' => [
            'result' => 'success',
            'count' => 1
        ]
    ];

    /**
     * @var string
     */
    private string $lastOperation = 'select';

    /**
     * @var array
     */
    private array $conditions = [];

    /**
     * @var int
     */
    private int $limitValue = 10;

    /**
     * @var int
     */
    private int $offsetValue = 0;

    /**
     * @var string
     */
    private string $indexName = '';

    /**
     * @var array
     */
    private array $fields = ['*'];

    /**
     * Select fields from the index
     * 
     * @param array|string $fields
     * @return QueryBuilderInterface
     */
    public function select(array|string $fields = '*'): QueryBuilderInterface
    {
        $this->lastOperation = 'select';
        $this->fields = is_array($fields) ? $fields : [$fields];
        return $this;
    }

    /**
     * Set the index to query from
     * 
     * @param string $index
     * @return QueryBuilderInterface
     */
    public function from(string $index): QueryBuilderInterface
    {
        $this->indexName = $index;
        return $this;
    }

    /**
     * Set the index to insert into
     * 
     * @param string $index
     * @return QueryBuilderInterface
     */
    public function into(string $index): QueryBuilderInterface
    {
        $this->indexName = $index;
        return $this;
    }

    /**
     * Add a where condition
     * 
     * @param string $field
     * @param mixed $operator
     * @param mixed $value
     * @param string $context
     * @return QueryBuilderInterface
     */
    public function where(string $field, mixed $operator, mixed $value = null, string $context = 'must'): QueryBuilderInterface
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->conditions[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value
        ];

        return $this;
    }

    /**
     * Set limit for query
     * 
     * @param int $limit
     * @return QueryBuilderInterface
     */
    public function limit(int $limit): QueryBuilderInterface
    {
        $this->limitValue = $limit;
        return $this;
    }

    /**
     * Set offset for query
     * 
     * @param int $offset
     * @return QueryBuilderInterface
     */
    public function offset(int $offset): QueryBuilderInterface
    {
        $this->offsetValue = $offset;
        return $this;
    }

    /**
     * Insert a record
     * 
     * @param array $data
     * @return array
     */
    public function insert(array $data): array
    {
        $this->lastOperation = 'insert';
        return $this->execute();
    }

    /**
     * Update a record
     * 
     * @param string $id
     * @param array $data
     * @return array
     */
    public function update(string $id, array $data): array
    {
        $this->lastOperation = 'update';
        return $this->execute();
    }

    /**
     * Delete a record
     * 
     * @param string $id
     * @return array
     */
    public function delete(string $id): array
    {
        $this->lastOperation = 'delete';
        return $this->execute();
    }

    /**
     * Count records
     * 
     * @return int
     */
    public function count(): int
    {
        return $this->mockResults['count']['count'];
    }

    /**
     * Join with another index
     * 
     * @param array|string $index
     * @return QueryBuilderInterface
     */
    public function join(array|string $index): QueryBuilderInterface
    {
        return $this;
    }
    
    /**
     * Add a where condition with AND operator
     *
     * @param string $field
     * @param mixed $operator
     * @param mixed $value
     * @param string $context
     * @return QueryBuilderInterface
     */
    public function andWhere(string $field, mixed $operator, mixed $value = null, string $context = 'must'): QueryBuilderInterface
    {
        return $this->where($field, $operator, $value, $context);
    }
    
    /**
     * Add a where condition with OR operator
     *
     * @param string $field
     * @param mixed $operator
     * @param mixed $value
     * @param string $context
     * @return QueryBuilderInterface
     */
    public function orWhere(string $field, mixed $operator, mixed $value = null, string $context = 'must'): QueryBuilderInterface
    {
        return $this->where($field, $operator, $value, $context);
    }
    
    /**
     * Add a where condition with MUST context
     *
     * @param callable $callback
     * @return QueryBuilderInterface
     */
    public function whereMust(callable $callback): QueryBuilderInterface
    {
        return $this;
    }
    
    /**
     * Add a where condition with MUST_NOT context
     *
     * @param callable $callback
     * @return QueryBuilderInterface
     */
    public function whereMustNot(callable $callback): QueryBuilderInterface
    {
        return $this;
    }
    
    /**
     * Add a where condition with SHOULD context
     *
     * @param callable $callback
     * @return QueryBuilderInterface
     */
    public function whereShould(callable $callback): QueryBuilderInterface
    {
        return $this;
    }
    
    /**
     * Add a where condition with FILTER context
     *
     * @param callable $callback
     * @return QueryBuilderInterface
     */
    public function whereFilter(callable $callback): QueryBuilderInterface
    {
        return $this;
    }
    
    /**
     * Add a sort condition
     *
     * @param string $field
     * @param string $direction
     * @return QueryBuilderInterface
     */
    public function orderBy(string $field, string $direction = 'asc'): QueryBuilderInterface
    {
        return $this;
    }
    
    /**
     * Add a raw query condition
     *
     * @param array $query
     * @return QueryBuilderInterface
     */
    public function raw(array $query): QueryBuilderInterface
    {
        return $this;
    }

    /**
     * Execute the query and return results
     * 
     * @return array
     */
    public function execute(): array
    {
        if ($this->lastOperation === 'select') {
            return $this->mockResults[$this->lastOperation];
        } elseif ($this->lastOperation === 'insert' || $this->lastOperation === 'update') {
            return [
                'result' => $this->mockResults[$this->lastOperation]['result'],
                'data' => $this->mockResults[$this->lastOperation]['data']
            ];
        } else {
            return $this->mockResults[$this->lastOperation];
        }
    }

    /**
     * Simulate PHPUnit's willReturnSelf() for mocks
     * 
     * @return $this
     */
    public function willReturnSelf(): self
    {
        return $this;
    }

    /**
     * Set the count result for testing exists() method
     * 
     * @param int $count
     * @return $this
     */
    public function setCountResult(int $count): self
    {
        $this->mockResults['count']['count'] = $count;
        return $this;
    }

    /**
     * Set the delete result for testing delete() method
     * 
     * @param string $result 'success' or 'error'
     * @return $this
     */
    public function setDeleteResult(string $result): self
    {
        if ($result === 'error') {
            $this->mockResults['delete']['result'] = 'error';
            $this->mockResults['delete']['affected'] = 0;
        } else {
            $this->mockResults['delete']['result'] = 'deleted';
            $this->mockResults['delete']['affected'] = 1;
        }
        return $this;
    }

    /**
     * Set the search results for testing search() method
     * 
     * @param array $results Array of entity data
     * @return $this
     */
    public function setSearchResults(array $results): self
    {
        $formattedResults = [];
        foreach ($results as $result) {
            $formattedResults[] = ['data' => $result];
        }
        $this->mockResults['select']['data'] = $formattedResults;
        return $this;
    }

    /**
     * Set the paginate results for testing paginate() method
     * 
     * @param array $results Array of entity data
     * @param int $total Total number of records
     * @param int $perPage Records per page
     * @param int $currentPage Current page number
     * @return $this
     */
    public function setPaginateResults(array $results, int $total = 10, int $perPage = 10, int $currentPage = 1): self
    {
        $formattedResults = [];
        foreach ($results as $result) {
            $formattedResults[] = ['data' => $result];
        }
        $this->mockResults['select']['data'] = $formattedResults;
        $this->mockResults['select']['total'] = $total;
        $this->mockResults['select']['per_page'] = $perPage;
        $this->mockResults['select']['current_page'] = $currentPage;
        return $this;
    }
}
