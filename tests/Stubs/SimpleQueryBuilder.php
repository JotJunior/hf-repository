<?php

declare(strict_types=1);

namespace Jot\HfRepository\Tests\Stubs;

use Jot\HfElastic\Contracts\QueryBuilderInterface;

/**
 * Simplified implementation of QueryBuilderInterface for testing.
 * This implementation only includes methods that are actually used in the Repository tests.
 */
class SimpleQueryBuilder implements QueryBuilderInterface
{
    /**
     * @var array
     */
    private array $mockResults = [
        'result' => 'success',
        'data' => [['id' => 'test-id-123', 'name' => 'Updated Entity']]
    ];

    /**
     * @var int
     */
    private int $countResult = 1;

    /**
     * @var string
     */
    private string $deleteResult = 'deleted';

    /**
     * Select fields from the index
     * 
     * @param array|string $fields
     * @return QueryBuilderInterface
     */
    public function select(array|string $fields = '*'): QueryBuilderInterface
    {
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
        return [
            'result' => 'created',
            'data' => $data
        ];
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
        return [
            'result' => 'updated',
            'data' => $data
        ];
    }

    /**
     * Delete a record
     * 
     * @param string $id
     * @return array
     */
    public function delete(string $id): array
    {
        return [
            'result' => $this->deleteResult,
            'affected' => $this->deleteResult === 'deleted' ? 1 : 0
        ];
    }

    /**
     * Count records
     * 
     * @return int
     */
    public function count(): int
    {
        return $this->countResult;
    }

    /**
     * Execute the query and return results
     * 
     * @return array
     */
    public function execute(): array
    {
        return $this->mockResults;
    }

    /**
     * Set the search results for testing
     * 
     * @param array $results
     * @return $this
     */
    public function setSearchResults(array $results): self
    {
        $this->mockResults = [
            'result' => 'success',
            'data' => $results
        ];
        return $this;
    }

    /**
     * Set the count result for testing
     * 
     * @param int $count
     * @return $this
     */
    public function setCountResult(int $count): self
    {
        $this->countResult = $count;
        return $this;
    }

    /**
     * Set the delete result for testing
     * 
     * @param string $result
     * @return $this
     */
    public function setDeleteResult(string $result): self
    {
        $this->deleteResult = $result;
        return $this;
    }

    // Implementações vazias para os métodos restantes da interface
    public function andWhere(string $field, mixed $operator, mixed $value = null, string $context = 'must'): QueryBuilderInterface { return $this; }
    public function orWhere(string $field, mixed $operator, mixed $value = null, string $context = 'must'): QueryBuilderInterface { return $this; }
    public function whereMust(callable $callback): QueryBuilderInterface { return $this; }
    public function whereMustNot(callable $callback): QueryBuilderInterface { return $this; }
    public function whereShould(callable $callback): QueryBuilderInterface { return $this; }
    public function whereFilter(callable $callback): QueryBuilderInterface { return $this; }
    public function whereNested(string $field, callable $callback): QueryBuilderInterface { return $this; }
    public function geoDistance(string $field, string $location, string $distance): QueryBuilderInterface { return $this; }
    public function withSuffix(string $suffix): QueryBuilderInterface { return $this; }
    public function orderBy(string $field, string $direction = 'asc'): QueryBuilderInterface { return $this; }
    public function raw(array $query): QueryBuilderInterface { return $this; }
    public function join(array|string $index): QueryBuilderInterface { return $this; }
    public function toArray(): array { return []; }
}
