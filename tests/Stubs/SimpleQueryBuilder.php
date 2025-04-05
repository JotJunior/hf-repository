<?php

declare(strict_types=1);
/**
 * This file is part of hf-repository
 *
 * @link     https://github.com/JotJunior/hf-repository
 * @contact  hf-repository@jot.com.br
 * @license  MIT
 */

namespace Jot\HfRepository\Tests\Stubs;

use Jot\HfElastic\Contracts\QueryBuilderInterface;

/**
 * Simplified implementation of QueryBuilderInterface for testing.
 * This implementation only includes methods that are actually used in the Repository tests.
 */
class SimpleQueryBuilder implements QueryBuilderInterface
{
    private array $mockResults = [
        'result' => 'success',
        'data' => [['id' => 'test-id-123', 'name' => 'Updated Entity']],
    ];

    private int $countResult = 1;

    private string $deleteResult = 'deleted';

    /**
     * Select fields from the index.
     */
    public function select(array|string $fields = '*'): QueryBuilderInterface
    {
        return $this;
    }

    /**
     * Set the index to query from.
     */
    public function from(string $index): QueryBuilderInterface
    {
        return $this;
    }

    /**
     * Set the index to insert into.
     */
    public function into(string $index): QueryBuilderInterface
    {
        return $this;
    }

    /**
     * Add a where condition.
     */
    public function where(string $field, mixed $operator, mixed $value = null, string $context = 'must'): QueryBuilderInterface
    {
        return $this;
    }

    /**
     * Set limit for query.
     */
    public function limit(int $limit): QueryBuilderInterface
    {
        return $this;
    }

    /**
     * Set offset for query.
     */
    public function offset(int $offset): QueryBuilderInterface
    {
        return $this;
    }

    /**
     * Insert a record.
     */
    public function insert(array $data): array
    {
        return [
            'result' => 'created',
            'data' => $data,
        ];
    }

    /**
     * Update a record.
     */
    public function update(string $id, array $data): array
    {
        return [
            'result' => 'updated',
            'data' => $data,
        ];
    }

    /**
     * Delete a record.
     */
    public function delete(string $id): array
    {
        return [
            'result' => $this->deleteResult,
            'affected' => $this->deleteResult === 'deleted' ? 1 : 0,
        ];
    }

    /**
     * Count records.
     */
    public function count(): int
    {
        return $this->countResult;
    }

    /**
     * Execute the query and return results.
     */
    public function execute(): array
    {
        return $this->mockResults;
    }

    /**
     * Set the search results for testing.
     *
     * @return $this
     */
    public function setSearchResults(array $results): self
    {
        $this->mockResults = [
            'result' => 'success',
            'data' => $results,
        ];
        return $this;
    }

    /**
     * Set the count result for testing.
     *
     * @return $this
     */
    public function setCountResult(int $count): self
    {
        $this->countResult = $count;
        return $this;
    }

    /**
     * Set the delete result for testing.
     *
     * @return $this
     */
    public function setDeleteResult(string $result): self
    {
        $this->deleteResult = $result;
        return $this;
    }

    // Implementações vazias para os métodos restantes da interface
    public function andWhere(string $field, mixed $operator, mixed $value = null, string $context = 'must'): QueryBuilderInterface
    {
        return $this;
    }

    public function orWhere(string $field, mixed $operator, mixed $value = null, string $context = 'must'): QueryBuilderInterface
    {
        return $this;
    }

    public function whereMust(callable $callback): QueryBuilderInterface
    {
        return $this;
    }

    public function whereMustNot(callable $callback): QueryBuilderInterface
    {
        return $this;
    }

    public function whereShould(callable $callback): QueryBuilderInterface
    {
        return $this;
    }

    public function whereFilter(callable $callback): QueryBuilderInterface
    {
        return $this;
    }

    public function whereNested(string $field, callable $callback): QueryBuilderInterface
    {
        return $this;
    }

    public function geoDistance(string $field, string $location, string $distance): QueryBuilderInterface
    {
        return $this;
    }

    public function withSuffix(string $suffix): QueryBuilderInterface
    {
        return $this;
    }

    public function orderBy(string $field, string $direction = 'asc'): QueryBuilderInterface
    {
        return $this;
    }

    public function raw(array $query): QueryBuilderInterface
    {
        return $this;
    }

    public function join(array|string $index): QueryBuilderInterface
    {
        return $this;
    }

    public function toArray(): array
    {
        return [];
    }
}
